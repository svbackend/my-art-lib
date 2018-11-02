<?php

namespace App\Actors\Utils;

// TODO need to refactor.. But I need to find more information about best practises, some recommendations and so on
// I want to make easy way to change urls to images (for example if we would have some new server for files etc)
class ActorPhoto
{
    const TMDB_BASE_URL = 'https://image.tmdb.org/t/p/original';
    const BASE_URL = '/f/actors/{actorId}/photo.jpg';
    const BASE_PATH = __DIR__.'/../../../public'.self::BASE_URL;

    public static function savePhoto(int $actorId, string $photoUrl): ?string
    {
        $saveTo = str_replace('{actorId}', $actorId, self::BASE_PATH);
        $destinationDir = \dirname($saveTo);

        if (is_dir($destinationDir) === false) {
            if (is_file($destinationDir)) {
                unlink($destinationDir);
            }
            mkdir($destinationDir);
        }

        $ch = curl_init($photoUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw = curl_exec($ch);

        if (curl_errno($ch) !== 0) {
            curl_close($ch);

            return null;
        }

        curl_close($ch);

        if (file_exists($saveTo)) {
            unlink($saveTo);
        }
        $fp = fopen($saveTo, 'xb');
        fwrite($fp, $raw);
        fclose($fp);
        chmod($saveTo, 0777);
        chmod($destinationDir, 0777);

        return $saveTo;
    }

    public static function getUrl(int $actorId): string
    {
        return str_replace('{actorId}', $actorId, self::BASE_URL);
    }
}
