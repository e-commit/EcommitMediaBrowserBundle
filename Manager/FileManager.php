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
    protected $root_path;
    
    protected $root_dir;
    
    protected $request_path;
    
    protected $request_dir = null;
    
    protected $parent_dir = null;
    
    protected $is_root = true;

    /**
     * Constructor
     * 
     * @param string $root_dir  The root dir
     * @param string $kernel_root_dir  The root dir
     * @throws MediaBrowserException 
     */
    public function __construct($root_dir, $kernel_root_dir)
    {
        $root_dir = \str_replace('\\', '/', $root_dir);
        if(\substr($root_dir, 0, 1) == '/')
        {
            $root_dir = \substr($root_dir, 1);
        }
        if(\substr($root_dir, -1) != '/')
        {
            $root_dir = $root_dir.'/';
        }
        
        $root_path = \realpath($kernel_root_dir.'/../web/'.$root_dir);
        if(!\is_dir($root_path))
        {
            throw new MediaBrowserException('Bad root path');
        }
        
        $this->root_path = $root_path;
        $this->request_path = $root_path;
        $this->root_dir = $root_dir;
    }
    
    /**
     * Sets the request directory
     * 
     * @param string $request_dir  The resquest directory
     * @throws MediaBrowserException 
     */
    public function setRequestDir($request_dir)
    {
        if(empty($request_dir))
        {
            return;
        }
        
        $request_path = \realpath(sprintf('%s/%s', $this->root_path, $request_dir));
        if($request_path == $this->root_path)
        {
            return;
        }
        if($request_path && $this->isSecured($request_path) && \is_dir($request_path))
        {
            $this->request_path = $request_path;
            $this->request_dir = $this->getRelativeDir($request_path);
            $parent_path = \realpath(sprintf('%s/../', $request_path));
            $this->parent_dir = $this->getRelativeDir($parent_path);
            $this->is_root = false;
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
        return $this->root_path;
    }
    
    /**
     * Gets the root directory (relative path to web dir)
     * Begins without slash and ends with slash
     * 
     * @return string
     */
    public function getRootDir()
    {
        return $this->root_dir;
    }
    
    /**
     * Gets the request dir (relative path to root dir)
     * Begins without slash and ends with slash
     * 
     * @return string
     */
    public function getRequestDir()
    {
        return $this->request_dir;
    }
    
    /**
     * Gets the parent dir (relative path to root dir)
     * Begins without slash and ends with slash
     * 
     * @return string 
     */
    public function getParentDir()
    {
        return $this->parent_dir;
    }
    
    /**
     * Gets the request path
     * 
     * @return string 
     */
    public function getRequestPath()
    {
        return $this->request_path;
    }
    
    /**
     * Returns true if request dir is root
     * 
     * @return bool 
     */
    public function isRoot()
    {
        return $this->is_root;
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
                ->in($this->request_path)
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
                ->in($this->request_path)
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
        foreach($this->getFiles() as $file)
        {
            $tmp['file'] = $file;
            $mime_type = \finfo_file($finfo, $file->getPathname());
            $tmp['is_image'] = \preg_match('/^image\//', $mime_type);
            $files[] = $tmp;
        }
        return $files;
    }
    
    /**
     * Returns true if element exists and is inside root path
     * 
     * @param string $element_name  Element name
     * @param bool $full_path  True if element name is a full path
     * @return boolean 
     */
    public function elementExists($element_name, $full_path = false)
    {
        if($full_path)
        {
            $element_path = \realpath(sprintf('%s/%s', $this->root_path, $element_name));
        }
        else
        {
            $element_path = \realpath(\sprintf('%s/%s', $this->request_path, $element_name));
        }
        
        if($element_path && $this->isSecured($element_path))
        {
            return \file_exists($element_path);
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
        try
        {
            if(\is_writable($this->request_path))
            {
                $file->move($this->request_path, $file->getClientOriginalName());
                $path = \realpath(\sprintf('%s/%s', $this->request_path, $file->getClientOriginalName()));
                @\chmod($path, 0777);
            }
            else
            {
                throw new MediaBrowserException('The folder is not writable');
            }
        }
        catch(FileException $e)
        {
            throw new MediaBrowserException($e->getMessage());
        }
    }
    
    /**
     * Creates new folder
     * Warning:
     *      - The new folder must not exist
     *      - The folder name must  be correct
     * 
     * @param string $folder_name  The folder name
     * @throws MediaBrowserException 
     */
    public function createFolder($folder_name)
    {
        $path = \sprintf('%s/%s', $this->request_path, $folder_name);
        if(!\realpath($path))
        {
            if(\is_writable($this->request_path))
            {
                if(!@\mkdir($path, 0777))
                {
                    throw new MediaBrowserException('Error during the folder creation process');
                }
            }
            else
            {
                throw new MediaBrowserException('The folder is not writable');
            }
        }
    }
    
    /**
     * Returns SplFileInfo object if it exists and is inside root path
     * 
     * @param string $element_path  Element path
     * @return \SplFileInfo
     * @throws MediaBrowserException 
     */
    public function getElement($element_path)
    {
        if($this->elementExists($element_path, true))
        {
            $element_path = \realpath(\sprintf('%s/%s', $this->root_path, $element_path));
            return new \SplFileInfo($element_path);
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
        return $this->root_dir.$this->getRelativeFile($file->getPathname());
    }
    
    /**
     * Renames an element
     * Warning:
     *      - The element must be inside root path
     *      - The new name must not exist
     *      - The new name must be correct
     * 
     * @param \SplFileInfo $element
     * @param string $new_name
     * @throws MediaBrowserException 
     */
    public function rename(\SplFileInfo $element, $new_name)
    {
        if($element->getPathname() == $this->root_path)
        {
            throw new MediaBrowserException('Impossible to rename root path');
        }
        if($element->isDir())
        {
            //Dir
            $parent_path = \realpath(\sprintf('%s/../', $element->getPathname()));
        }
        else
        {
            //File
            $parent_path = $element->getPath();
        }
        $new_path = \sprintf('%s/%s', $parent_path, $new_name);
        if(\file_exists($new_path))
        {
            throw new MediaBrowserException('The element already exists');
        }
        if(!@\rename($element->getPathname(), $new_path))
        {
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
        if($element->getPathname() == $this->root_path)
        {
            throw new MediaBrowserException('Impossible to delete root path');
        }
        if($element->isDir())
        {
            //Dir
            if(!$this->deleteRecursive($element))
            {
                throw new MediaBrowserException('Impossible to delete element');
            }
        }
        else
        {
            //File
            if(!@\unlink($element->getPathname()))
            {
                throw new MediaBrowserException('Impossible to delete element');
            }
        }
    }
    
    /**
     * Returns true is element is secured (inside the root path)
     * 
     * @param string $request_path
     * @return bool 
     */
    protected function isSecured($request_path)
    {
        $root_path_escaped = addslashes(\realpath($this->root_path)); //Escape '  " and \ (not escape /)
        $root_path_escaped = \str_replace('/', '\\/', $root_path_escaped); //Escape /
        
        return \preg_match('/^'.$root_path_escaped.'/', \realpath($request_path));
    }
    
    /**
     * Gets the relative dir
     * 
     * @param string $request_path
     * @return null|string 
     */
    public function getRelativeDir($request_path)
    {
        if($request_path == $this->root_path)
        {
            return null;
        }
        
        $relative_dir = \str_replace($this->root_path.DIRECTORY_SEPARATOR, '', $request_path);
        $relative_dir = \str_replace('\\', '/', $relative_dir).'/';
        return $relative_dir;
    }
    
    /**
     * Gets the relative file
     * 
     * @param string $request_path
     * @return string 
     */
    protected function getRelativeFile($request_path)
    {
        $relative_file = \str_replace($this->root_path.DIRECTORY_SEPARATOR, '', $request_path);
        $relative_file = \str_replace('\\', '/', $relative_file);
        return $relative_file;
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
        foreach($files as $file)
        {
            @\unlink($file);
        }
        $finder = new Finder();
        $folders = \iterator_to_array($finder->directories()->in($dir->getPathname()));
        foreach(\array_reverse($folders) as $folder)
        {
            @\rmdir($folder);
        }
        return @\rmdir($dir);
    }
}