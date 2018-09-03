<?php

namespace App\Movies\Utils;

class Poster
{
    const TMDB_BASE_URL = 'https://image.tmdb.org/t/p/original';
    const BASE_URL = '/f/movies/{movieId}/poster.jpg';
    const BASE_PATH = __DIR__.'/../../../public/f/movies/{movieId}/poster.jpg';

    /**
     * @param int    $movieId
     * @param string $posterUrl
     *
     * @return null|string
     */
    public static function savePoster(int $movieId, string $posterUrl): ?string
    {
        $saveTo = str_replace('{movieId}', $movieId, self::BASE_PATH);
        $destinationDir = dirname($saveTo);

        if (is_dir($destinationDir) === false) {
            if (is_file($destinationDir)) {
                unlink($destinationDir);
            }
            mkdir($destinationDir);
        }

        $ch = curl_init($posterUrl);
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
            self::removePoster($movieId);
        }
        $fp = fopen($saveTo, 'x');
        fwrite($fp, $raw);
        fclose($fp);
        chmod($saveTo, 0777);
        chmod($destinationDir, 0777);

        return $saveTo;
    }

    public static function removePoster(int $movieId): void
    {
        $saveTo = \str_replace('{movieId}', $movieId, self::BASE_PATH);
        $dir = \dirname($saveTo);
        $files = \scandir($dir);
        foreach ($files as $file) {
            if (substr($file, 0, 6) === 'poster' && strpos($file, '.') !== false) {
                // if its file like poster.jpg or poster.260x380.jpg - remove it
                unlink($dir . DIRECTORY_SEPARATOR . $file);
            }
        }
    }

    /**
     * @param int $movieId
     *
     * @return string
     */
    public static function getUrl(int $movieId): string
    {
        return str_replace('{movieId}', $movieId, self::BASE_URL);
    }
}
