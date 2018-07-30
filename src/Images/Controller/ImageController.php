<?php

namespace App\Images\Controller;

use App\Controller\BaseController;
use Gumlet\ImageResize;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends BaseController
{
    /**
     * @Route("/f/{image}", methods={"GET"}, name="resizeImage", requirements={"image"=".+"})
     * @param string $image
     * @return BinaryFileResponse|Response
     * @throws \Gumlet\ImageResizeException
     */
    public function resizeImage(string $image)
    {
        $pathToFile = __DIR__ . '/../../../public/f/' . $image;
        $imagePathParts = explode('/', $image);
        $filename = end($imagePathParts);
        $filenameParts = explode('.', $filename);
        $ext = end($filenameParts);

        if (in_array($ext, ['jpg']) === false) {
            return new Response('Image not found (404)', 404);
        }
        $size = $filenameParts[1]; // string '64x64'
        $originalFile = str_replace(".$size", '', $pathToFile);

        if (file_exists($originalFile) === false) {
            return new Response('Image not found (404)', 404);
        }

        // resize image and then return
        list($width, $height) = explode('x', $size);
        $resizer = new ImageResize($originalFile);
        $resizer->crop($width, $height);
        $resizer->save($pathToFile);

        return new BinaryFileResponse($pathToFile);
    }
}