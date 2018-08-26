<?php

declare(strict_types=1);

namespace App\Translation;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Serializer;

/**
 * Trait TranslatedResponseTrait.
 *
 * @method get($id)
 * @method has($id):bool
 */
trait TranslatedResponseTrait
{
    public function translateResponse($response, array $context): array
    {
        /** @var $normalizer Serializer */
        $normalizer = $this->get('serializer');
        $response = $normalizer->normalize($response, null, $context);
        $response = $this->translateEntities(is_array($response) ? $response : [$response]);

        return $response;
    }

    protected function translateEntities(array $data, $recursive = true): array
    {
        $translatedData = [];

        foreach ($data as $key => $value) {
            if ($key === 'translations') {
                $translatedData = array_merge($translatedData, $this->getEntityTranslation($value));

                if ($recursive === true) {
                    continue;
                }
                break;
            }

            if (is_array($value)) {
                $data[$key] = $this->translateEntities($value, $recursive);
            }
        }

        unset($data['translations']);
        $data = array_merge($data, $translatedData);

        return $data;
    }

    private function getEntityTranslation(array $translations)
    {
        $userLocale = $this->getUserPreferredLocale(array_keys($translations));

        return $translations[$userLocale];
    }

    /**
     * @param array $locales
     *
     * @return null|string
     */
    private function getUserPreferredLocale(array $locales = [])
    {
        if (!isset($locales[0])) {
            // there's no translations for this entity
            throw new NotFoundHttpException();
        }

        /** @var $requestStack RequestStack */
        $requestStack = $this->get('request_stack');
        $request = $requestStack->getCurrentRequest();

        $preferredLocale = $request->getPreferredLanguage($locales);

        $locale = $request->getLocale(); // can be set by query param (?language=ru) or by symfony
        if ($locale !== $request->getDefaultLocale() && in_array($locale, $locales, true) === true) {
            $preferredLocale = $locale;
        }

        if ($request->query->get('language') !== null && $locale === $request->getDefaultLocale()) {
            $preferredLocale = $locale;
        }

        return $preferredLocale;
    }
}
