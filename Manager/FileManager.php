<?php

/*
 * This file is part of the EcommitMediaBrowserBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\MediaBrowserBundle\Manager;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileManager
{
    protected $rootPath;

    protected $rootDir;

    protected $requestPath;

    protected $requestDir = null;

    protected $parentDir = null;

    protected $isRoot = true;

    /**
     * Constructor
     *
     * @param string $rootDir The root dir
     * @param string $kernelRootDir The root dir
     * @throws MediaBrowserException
     */
    public function __construct($rootDir, $kernelRootDir)
    {
        $rootDir = \str_replace('\\', '/', $rootDir);
        if (\substr($rootDir, 0, 1) == '/') {
            $rootDir = \substr($rootDir, 1);
        }
        if (\substr($rootDir, -1) != '/') {
            $rootDir = $rootDir . '/';
        }

        $rootPath = \realpath($kernelRootDir . '/../web/' . $rootDir);
        if (!\is_dir($rootPath)) {
            throw new MediaBrowserException('Bad root path');
        }

        $this->rootPath = $rootPath;
        $this->requestPath = $rootPath;
        $this->rootDir = $rootDir;
    }

    /**
     * Sets the request directory
     *
     * @param string $requestDir The resquest directory
     * @throws MediaBrowserException
     */
    public function setRequestDir($requestDir)
    {
        if (empty($requestDir)) {
            return;
        }

        $requestPath = \realpath(sprintf('%s/%s', $this->rootPath, $requestDir));
        if ($requestPath == $this->rootPath) {
            return;
        }
        if ($requestPath && $this->isSecured($requestPath) && \is_dir($requestPath)) {
            $this->requestPath = $requestPath;
            $this->requestDir = $this->getRelativeDir($requestPath);
            $parentPath = \realpath(sprintf('%s/../', $requestPath));
            $this->parentDir = $this->getRelativeDir($parentPath);
            $this->isRoot = false;

            return true;
        }
        throw new MediaBrowserException('Bad path');
    }

    /**
     * Gets the root path
     *
     * @return string
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * Gets the root directory (relative path to web dir)
     * Begins without slash and ends with slash
     *
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * Gets the request dir (relative path to root dir)
     * Begins without slash and ends with slash
     *
     * @return string
     */
    public function getRequestDir()
    {
        return $this->requestDir;
    }

    /**
     * Gets the parent dir (relative path to root dir)
     * Begins without slash and ends with slash
     *
     * @return string
     */
    public function getParentDir()
    {
        return $this->parentDir;
    }

    /**
     * Gets the request path
     *
     * @return string
     */
    public function getRequestPath()
    {
        return $this->requestPath;
    }
    
    /**
     * Returns true if request dir is root
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->isRoot;
    }

    /**
     * Get folders inside request dir
     *
     * @return Finder
     */
    public function getFolders()
    {
        $finder = new Finder();

        return $finder->directories()
            ->in($this->requestPath)
            ->depth('== 0')
            ->sortByName();
    }

    /**
     * Get files inside request dir
     *
     * @return Finder
     */
    public function getFiles()
    {
        $finder = new Finder();

        return $finder->files()
            ->in($this->requestPath)
            ->depth('== 0')
            ->sortByName();
    }

    /**
     * Get files inside request dir. The result is an array:
     *      - file, the SplFileInfo object
     *      - is_image, true is file is an image
     *
     * @return array
     */
    public function getFilesWithType()
    {
        $files = array();
        $finfo = \finfo_open(\FILEINFO_MIME_TYPE);
        foreach ($this->getFiles() as $file) {
            $tmp['file'] = $file;
            $mimeType = \finfo_file($finfo, $file->getPathname());
            $tmp['is_image'] = \preg_match('/^image\//', $mimeType);
            $files[] = $tmp;
        }

        return $files;
    }

    /**
     * Returns true if element exists and is inside root path
     *
     * @param string $elementName Element name
     * @param bool $fullPath True if element name is a full path
     * @return boolean
     */
    public function elementExists($elementName, $fullPath = false)
    {
        if ($fullPath) {
            $elementPath = \realpath(sprintf('%s/%s', $this->rootPath, $elementName));
        } else {
            $elementPath = \realpath(\sprintf('%s/%s', $this->requestPath, $elementName));
        }

        if ($elementPath && $this->isSecured($elementPath)) {
            return \file_exists($elementPath);
        }

        return false;
    }

    /**
     * Uploads new file
     * Warning:
     *      - The new file must not exist
     *      - The filename must be correct
     *
     * @param UploadedFile $file
     * @throws MediaBrowserException
     */
    public function upload(UploadedFile $file)
    {
        try {
            if (\is_writable($this->requestPath)) {
                $file->move($this->requestPath, $file->getClientOriginalName());
                $path = \realpath(\sprintf('%s/%s', $this->requestPath, $file->getClientOriginalName()));
                @\chmod($path, 0777);
            } else {
                throw new MediaBrowserException('The folder is not writable');
            }
        } catch (FileException $e) {
            throw new MediaBrowserException($e->getMessage());
        }
    }

    /**
     * Creates new folder
     * Warning:
     *      - The new folder must not exist
     *      - The folder name must  be correct
     *
     * @param string $folderName The folder name
     * @throws MediaBrowserException
     */
    public function createFolder($folderName)
    {
        $path = \sprintf('%s/%s', $this->requestPath, $folderName);
        if (!\realpath($path)) {
            if (\is_writable($this->requestPath)) {
                if (!@\mkdir($path, 0777)) {
                    throw new MediaBrowserException('Error during the folder creation process');
                }
            } else {
                throw new MediaBrowserException('The folder is not writable');
            }
        }
    }

    /**
     * Returns SplFileInfo object if it exists and is inside root path
     *
     * @param string $elementPath Element path
     * @return \SplFileInfo
     * @throws MediaBrowserException
     */
    public function getElement($elementPath)
    {
        if ($this->elementExists($elementPath, true)) {
            $elementPath = \realpath(\sprintf('%s/%s', $this->rootPath, $elementPath));

            return new \SplFileInfo($elementPath);
        }
        throw new MediaBrowserException('Element does not exist');
    }

    /**
     * Returns web url
     *
     * @param \SplFileInfo
     * @return type
     */
    public function getUrlElement(\SplFileInfo $file)
    {
        return $this->rootDir . $this->getRelativeFile($file->getPathname());
    }

    /**
     * Renames an element
     * Warning:
     *      - The element must be inside root path
     *      - The new name must not exist
     *      - The new name must be correct
     *
     * @param \SplFileInfo $element
     * @param string $newName
     * @throws MediaBrowserException
     */
    public function rename(\SplFileInfo $element, $newName)
    {
        if ($element->getPathname() == $this->rootPath) {
            throw new MediaBrowserException('Impossible to rename root path');
        }
        if ($element->isDir()) {
            //Dir
            $parentPath = \realpath(\sprintf('%s/../', $element->getPathname()));
        } else {
            //File
            $parentPath = $element->getPath();
        }
        $newPath = \sprintf('%s/%s', $parentPath, $newName);
        if (\file_exists($newPath)) {
            throw new MediaBrowserException('The element already exists');
        }
        if (!@\rename($element->getPathname(), $newPath)) {
            throw new MediaBrowserException('Impossible to rename element');
        }
    }

    /**
     * Deletes the element
     * Warning, the element must be inside the root path
     *
     * @param \SplFileInfo $element
     * @throws MediaBrowserException
     */
    public function delete(\SplFileInfo $element)
    {
        if ($element->getPathname() == $this->rootPath) {
            throw new MediaBrowserException('Impossible to delete root path');
        }
        if ($element->isDir()) {
            //Dir
            if (!$this->deleteRecursive($element)) {
                throw new MediaBrowserException('Impossible to delete element');
            }
        } else {
            //File
            if (!@\unlink($element->getPathname())) {
                throw new MediaBrowserException('Impossible to delete element');
            }
        }
    }

    /**
     * Returns true is element is secured (inside the root path)
     *
     * @param string $requestPath
     * @return bool
     */
    protected function isSecured($requestPath)
    {
        $rootPathEscaped = addslashes(\realpath($this->rootPath)); //Escape '  " and \ (not escape /)
        $rootPathEscaped = \str_replace('/', '\\/', $rootPathEscaped); //Escape /

        return \preg_match('/^' . $rootPathEscaped . '/', \realpath($requestPath));
    }

    /**
     * Gets the relative dir
     *
     * @param string $requestPath
     * @return null|string
     */
    public function getRelativeDir($requestPath)
    {
        if ($requestPath == $this->rootPath) {
            return null;
        }

        $relativeDir = \str_replace($this->rootPath . DIRECTORY_SEPARATOR, '', $requestPath);
        $relativeDir = \str_replace('\\', '/', $relativeDir) . '/';

        return $relativeDir;
    }

    /**
     * Gets the relative file
     *
     * @param string $requestPath
     * @return string
     */
    protected function getRelativeFile($requestPath)
    {
        $relativeFile = \str_replace($this->rootPath . DIRECTORY_SEPARATOR, '', $requestPath);
        $relativeFile = \str_replace('\\', '/', $relativeFile);

        return $relativeFile;
    }

    /**
     * Delete a folder
     *
     * @param \SplFileInfo $dir
     */
    protected function deleteRecursive(\SplFileInfo $dir)
    {
        $finder = new Finder();
        $files = $finder->files()->in($dir->getPathname());
        foreach ($files as $file) {
            @\unlink($file);
        }
        $finder = new Finder();
        $folders = \iterator_to_array($finder->directories()->in($dir->getPathname()));
        foreach (\array_reverse($folders) as $folder) {
            @\rmdir($folder);
        }

        return @\rmdir($dir);
    }
}
