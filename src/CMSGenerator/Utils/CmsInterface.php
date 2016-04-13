<?php
namespace Mouf\Cms\Generator\Utils;

/**
 * @author Jean-Baptiste Charron
 */
interface CmsInterface {

    /**
     * Remove accents from string in parameter
     *
     * @param string $string
     *
     * @return string
     */
    public function removeAccent($string);

    /**
     * Slugify a full file name with file extension, for example $_FILES["foo"]["name"]
     *
     * @param string $fileName
     *
     * @return string
     */
    public function slugifyFile($fileName);

    /**
     * Slugify a string in parameter
     *
     * @param string $title The title string to slugify
     *
     * @return string
     */
    public function slugify($title);

    /**
     * Save a file in the upload directory
     *
     * @param array  $uploadedFile      Is an array resulting of file upload, for example $_FILES["foo"] for <input type="file" name="foo" />
     * @param string $uploadDir         The upload directory
     * @param array  $allowedExtensions An array with allowed extensions for the file, for example ['jpg','png','bmp']
     *
     * @return string
     *
     * @throws \Exception
     */
    public function saveFile($uploadedFile, $uploadDir, $allowedExtensions);
}