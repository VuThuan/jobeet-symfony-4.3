<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Category;
use App\Form\Admin\CategoryType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends AbstractController
{
    /**
     * Lists all categories entities
     * 
     * @Route("/admin/categories", name="admin.category.list", methods="GET")
     * 
     * @param EntityManagerInterface $em
     * @param AdapterInterface $cache
     * 
     * @return Response
     */
    public function list(EntityManagerInterface $em, AdapterInterface $cache): Response
    {

        $item = $cache->getItem('Admin_categories');

        if (!$item->isHit()) {
            $item->set($em->getRepository(Category::class)->findAll());
            $cache->save($item);
        }
        $categories = $item->get();

        return $this->render('admin/category/list.html.twig', [
            'categories' => $categories
        ]);
    }

    /**
     * Create category
     * 
     * @Route("/admin/category/create", name="admin.category.create", methods="GET|POST")
     * 
     * @param Request $request
     * @param EntityManagerInterface $em
     * 
     * @return Response
     */
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            return $this->redirectToRoute('admin.category.list');
        }

        return $this->render('admin/category/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Edit category.
     *
     * @Route("/admin/category/{id}/edit", name="admin.category.edit", methods="GET|POST", requirements={"id" = "\d+"})
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param Category $category
     *
     * @return Response
     */
    public function edit(Request $request, EntityManagerInterface $em, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('admin.category.list');
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Delete category
     * 
     * @Route("/admin/category/{id}/delete", name="admin.category.delete", methods="DELETE", requirements={"id" = "\d+"})
     * 
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param Category $category
     * 
     * @return Response
     */
    public function delete(Request $request, EntityManagerInterface $em, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
        }

        return $this->redirectToRoute('admin.category.list');
    }
}
