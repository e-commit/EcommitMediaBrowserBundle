<?php

/*
 * This file is part of the EcommitMediaBrowserBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\MediaBrowserBundle\Controller;

use Ecommit\MediaBrowserBundle\Form\Model\File;
use Ecommit\MediaBrowserBundle\Form\Model\Folder;
use Ecommit\MediaBrowserBundle\Form\Type\FileType;
use Ecommit\MediaBrowserBundle\Form\Type\FolderType;
use Ecommit\MediaBrowserBundle\Manager\FileManager;
use Ecommit\MediaBrowserBundle\Manager\MediaBrowserException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DefaultController extends Controller
{
    protected function secure()
    {
        if(!$this->get('security.context')->isGranted('ROLE_USE_MEDIA_BROWSER'))
        {
            throw new AccessDeniedException();
        }
    }


    /**
     * @Route("/")
     */
    public function indexAction()
    {
        $this->secure();
        return $this->redirect($this->generateUrl('ecommitmediabrowser_show'));
    }
    
    /**
     * @Route("/show/{dir}", name="ecommitmediabrowser_show", 
     *      defaults={"dir"=""}, requirements={"dir"=".+"})
     * @Template("EcommitMediaBrowserBundle:Default:index.html.twig")
     */
    public function showAction($dir)
    {
        $this->secure();
        $manager = $this->getManager($dir);
        $form_file = $this->getFormFile($manager);
        $form_folder = $this->getFormFolder($manager);
        
        return array('manager' => $manager, 'form_file' => $form_file->createView(), 'form_folder' => $form_folder->createView());
    }
    
    /**
     * @Route("/upload/{dir}", name="ecommitmediabrowser_upload", 
     *      defaults={"dir"=""}, requirements={"dir"=".+"})
     * @Template("EcommitMediaBrowserBundle:Default:index.html.twig")
     */
    public function uploadAction(Request $request, $dir)
    {
        $this->secure();
        $manager = $this->getManager($dir);
        $form_file = $this->getFormFile($manager);
        $form_folder = $this->getFormFolder($manager);
        
        if($request->getMethod() == 'POST')
        {
            $form_file->handleRequest($request);
            if($form_file->isValid())
            {
                try
                {
                    $manager->upload($form_file->getData()->getFile());
                    $this->get('session')->getFlashBag()->add('ecommitmediabrowser', 'File uploaded');
                }
                catch(MediaBrowserException $e)
                {
                    $this->get('session')->getFlashBag()->add('ecommitmediabrowser', $e->getMessage());
                }
                
                return $this->redirect($this->generateUrl('ecommitmediabrowser_show', array('dir' => $dir)));
            }
        }
        
        return array('manager' => $manager, 'form_file' => $form_file->createView(), 'form_folder' => $form_folder->createView());
    }
    
    /**
     * @Route("/new_folder/{dir}", name="ecommitmediabrowser_new_folder", 
     *      defaults={"dir"=""}, requirements={"dir"=".+"})
     * @Template("EcommitMediaBrowserBundle:Default:index.html.twig")
     */
    public function newFolderAction(Request $request, $dir)
    {
        $this->secure();
        $manager = $this->getManager($dir);
        $form_file = $this->getFormFile($manager);
        $form_folder = $this->getFormFolder($manager);
        
        if($request->getMethod() == 'POST')
        {
            $form_folder->handleRequest($request);
            if($form_folder->isValid())
            {
                try
                {
                    $manager->createFolder($form_folder->getData()->getName());
                }
                catch(MediaBrowserException $e)
                {
                    $this->get('session')->getFlashBag()->add('ecommitmediabrowser', $e->getMessage());
                }
                
                return $this->redirect($this->generateUrl('ecommitmediabrowser_show', array('dir' => $dir)));
            }
        }
        
        return array('manager' => $manager, 'form_file' => $form_file->createView(), 'form_folder' => $form_folder->createView());
    }
    
    /**
     * @Route("/delete/{element}", name="ecommitmediabrowser_delete", 
     *      requirements={"element"=".+"})
     */
    public function deleteAction($element)
    {
        $this->secure();
        $manager = $this->getManager();
        $dir = $manager->getRequestDir();
        try
        {
            $element = $this->getElement($element);
            $parent_path = $this->getParentElementPath($element);
            $dir = $manager->getRelativeDir($parent_path);
            $manager->delete($element);
        }
        catch(MediaBrowserException $e)
        {
            $this->get('session')->getFlashBag()->add('ecommitmediabrowser', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('ecommitmediabrowser_show', array('dir' => $dir)));
    }
    
    /**
     * @Route("/rename/{element}", name="ecommitmediabrowser_rename", 
     *      requirements={"element"=".+"})
     */
    public function renameAction(Request $request, $element)
    {
        $this->secure();
        $new_name = $request->query->get('new_name', null);
        if(!$new_name || !\preg_match('/^[A-Za-z0-9\._-]+$/', $new_name))
        {
            throw $this->createNotFoundException('Bad value');
        }
        
        $manager = $this->getManager();
        $dir = $manager->getRequestDir();
        try
        {
            $element = $this->getElement($element);
            $parent_path = $this->getParentElementPath($element);
            $dir = $manager->getRelativeDir($parent_path);
            $manager->rename($element, $new_name);
        }
        catch(MediaBrowserException $e)
        {
            $this->get('session')->getFlashBag()->add('ecommitmediabrowser', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('ecommitmediabrowser_show', array('dir' => $dir)));
    }
    
    public function headerAction()
    {
        $this->get('ecommit_javascript.manager')->enablejQuery();
        
        $tiny_mce_popup = $this->container->getParameter('ecommit_media_browser.tiny_mce_popup');
        return new Response($this->get('templating.helper.assets')->getUrl($tiny_mce_popup));
    }
    
    /**
     * Returns manager
     * 
     * @param string $dir
     * @return Ecommit\MediaBrowserBundle\Manager\FileManager
     */
    protected function getManager($dir = null)
    {
        $manager = $this->get('ecommit_media_browser.manager');
        try
        {
            $manager->setRequestDir($dir);
        }
        catch(MediaBrowserException $e)
        {
            throw $this->createNotFoundException($e->getMessage());
        }
        return $manager;
    }
    
    /**
     * Returns File Form
     * @param Ecommit\MediaBrowserBundle\Manager\FileManager $manager
     * @return Symfony\Component\Form\Form
     */
    protected function getFormFile(FileManager $manager)
    {
        return $this->createForm(new FileType(), new File($manager->getRequestPath()));
    }
    
    /**
     * Returns Folder Form
     * @param Ecommit\MediaBrowserBundle\Manager\FileManager $manager
     * @return Symfony\Component\Form\Form
     */
    protected function getFormFolder(FileManager $manager)
    {
        return $this->createForm(new FolderType(), new Folder($manager->getRequestPath()));
    }
    
    /**
     * Returns SplFileInfo
     * @param string $path
     * @return \SplFileInfo
     */
    protected function getElement($path)
    {
        $manager = $this->getManager();
        if(!$manager->elementExists($path, true))
        {
            throw $this->createNotFoundException('Element does not exist');
        }
        $element_path = \realpath(\sprintf('%s/%s', $manager->getRootPath(), $path));
        if($element_path == $manager->getRootPath())
        {
            throw $this->createNotFoundException('Impossible to access root path');
        }
        return new \SplFileInfo($element_path);
    }
    
    /**
     * Returns parent element path
     * @param \SplFileInfo $element
     * @return string 
     */
    protected function getParentElementPath(\SplFileInfo $element)
    {
        if($element->isDir())
        {
            return \realpath(sprintf('%s/../', $element->getPathname()));
        }
        else
        {
            return $element->getPath();
        }
    }
}
