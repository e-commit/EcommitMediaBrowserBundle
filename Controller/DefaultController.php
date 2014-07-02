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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DefaultController extends Controller
{
    protected function secure()
    {
        if (!$this->get('security.context')->isGranted('ROLE_USE_MEDIA_BROWSER')) {
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
        $formFile = $this->getFormFile($manager);
        $formFolder = $this->getFormFolder($manager);

        return array(
            'manager' => $manager,
            'form_file' => $formFile->createView(),
            'form_folder' => $formFolder->createView()
        );
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
        $formFile = $this->getFormFile($manager);
        $formFolder = $this->getFormFolder($manager);

        if ($request->getMethod() == 'POST') {
            $formFile->handleRequest($request);
            if ($formFile->isValid()) {
                try {
                    $manager->upload($formFile->getData()->getFile());
                    $this->get('session')->getFlashBag()->add('ecommitmediabrowser', 'File uploaded');
                } catch (MediaBrowserException $e) {
                    $this->get('session')->getFlashBag()->add('ecommitmediabrowser', $e->getMessage());
                }

                return $this->redirect($this->generateUrl('ecommitmediabrowser_show', array('dir' => $dir)));
            }
        }

        return array(
            'manager' => $manager,
            'form_file' => $formFile->createView(),
            'form_folder' => $formFolder->createView()
        );
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
        $formFile = $this->getFormFile($manager);
        $formFolder = $this->getFormFolder($manager);

        if ($request->getMethod() == 'POST') {
            $formFolder->handleRequest($request);
            if ($formFolder->isValid()) {
                try {
                    $manager->createFolder($formFolder->getData()->getName());
                } catch (MediaBrowserException $e) {
                    $this->get('session')->getFlashBag()->add('ecommitmediabrowser', $e->getMessage());
                }

                return $this->redirect($this->generateUrl('ecommitmediabrowser_show', array('dir' => $dir)));
            }
        }

        return array(
            'manager' => $manager,
            'form_file' => $formFile->createView(),
            'form_folder' => $formFolder->createView()
        );
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
        try {
            $element = $this->getElement($element);
            $parentPath = $this->getParentElementPath($element);
            $dir = $manager->getRelativeDir($parentPath);
            $manager->delete($element);
        } catch (MediaBrowserException $e) {
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
        if (!$new_name || !\preg_match('/^[A-Za-z0-9\._-]+$/', $new_name)) {
            throw $this->createNotFoundException('Bad value');
        }

        $manager = $this->getManager();
        $dir = $manager->getRequestDir();
        try {
            $element = $this->getElement($element);
            $parentPath = $this->getParentElementPath($element);
            $dir = $manager->getRelativeDir($parentPath);
            $manager->rename($element, $new_name);
        } catch (MediaBrowserException $e) {
            $this->get('session')->getFlashBag()->add('ecommitmediabrowser', $e->getMessage());
        }

        return $this->redirect($this->generateUrl('ecommitmediabrowser_show', array('dir' => $dir)));
    }

    /**
     * @Template("EcommitMediaBrowserBundle::header.html.twig")
     */
    public function headerAction()
    {
        return array(
            'jquery_js' => $this->container->getParameter('ecommit_media_browser.jquery'),
        );
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
        try {
            $manager->setRequestDir($dir);
        } catch (MediaBrowserException $e) {
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
        if (!$manager->elementExists($path, true)) {
            throw $this->createNotFoundException('Element does not exist');
        }
        $elementPath = \realpath(\sprintf('%s/%s', $manager->getRootPath(), $path));
        if ($elementPath == $manager->getRootPath()) {
            throw $this->createNotFoundException('Impossible to access root path');
        }

        return new \SplFileInfo($elementPath);
    }

    /**
     * Returns parent element path
     * @param \SplFileInfo $element
     * @return string
     */
    protected function getParentElementPath(\SplFileInfo $element)
    {
        if ($element->isDir()) {
            return \realpath(sprintf('%s/../', $element->getPathname()));
        } else {
            return $element->getPath();
        }
    }
}
