<?php

namespace Mouf\Cms\Scaffolder\Utils;

use Psr\Http\Message\UploadedFileInterface;

/**
 * @author Jean-Baptiste Charron
 */
interface CmsInterface
{
    /**
     * Remove accents from string in parameter.
     *
     * @param string $string
     *
     * @return string
     */
    public function removeAccent(string $string) : string;

    /**
     * Slugify a full file name with file extension, for example $_FILES["foo"]["name"].
     *
     * @param string $fileName
     *
     * @return string
     */
    public function slugifyFile(string $fileName) : string;

    /**
     * Slugify a string in parameter.
     *
     * @param string $title The title string to slugify
     *
     * @return string
     */
    public function slugify(string $title) : string;

    /**
     * Save a file in the upload directory.
     *
     * @param UploadedFileInterface $uploadedFile      Result of the file upload
     * @param string                $uploadDir         The upload directory
     * @param array                 $allowedExtensions An array with allowed extensions for the file, for example ['jpg','png','bmp']
     *
     * @return string
     *
     * @throws \Exception
     */
    public function saveFile(UploadedFileInterface $uploadedFile, string $uploadDir, array $allowedExtensions) : string;
}
