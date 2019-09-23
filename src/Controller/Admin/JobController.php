<?php

namespace App\Controller\Admin;

use App\Entity\Job;
use App\Form\JobType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class JobController extends AbstractController
{
    /**
     * Lists all jobs entities.
     *
     * @Route("/admin/jobs/{page}",
     *     name="admin.job.list",
     *     methods="GET",
     *     defaults={"page": 1},
     *     requirements={"page" = "\d+"}
     * )
     *
     * @param EntityManagerInterface $em
     * @param PaginatorInterface $paginator
     * @param int $page
     * @param AdapterInterface $cache
     *
     * @return Response
     */
    public function list(EntityManagerInterface $em, PaginatorInterface $paginator, int $page, AdapterInterface $cache): Response
    {

        $item = $cache->getItem('listJobs');

        if (!$item->isHit()) {
            $item->set($paginator->paginate(
                $em->getRepository(Job::class)->createQueryBuilder('j'),
                $page,
                $this->getParameter('max_per_page'),
                [
                    PaginatorInterface::DEFAULT_SORT_FIELD_NAME => 'j.createdAt',
                    PaginatorInterface::DEFAULT_SORT_DIRECTION => 'DESC',
                ]
            ));
            $cache->save($item);
        }

        $jobs = $item->get();

        return $this->render('admin/job/list.html.twig', [
            'jobs' => $jobs,
        ]);
    }

    /**
     * Create job
     * 
     * @Route("/admin/job/create", name="admin.job.create", methods="GET|POST")
     * 
     * @param Request $request
     * @param EntityManagerInterface $em 
     * 
     * @return Response
     */
    public function create(Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $job = new Job();
        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logo')->getData();

            if ($logoFile instanceof UploadedFile) {
                $fileName = $fileUploader->upload($logoFile);

                $job->setLogo($fileName);
            }
            $em->persist($job);
            $em->flush();
        }

        return $this->render('admin/job/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Edit job
     * 
     * @Route("admin/job/{id}/edit", name="admin.job.edit", methods="GET|POST", requirements={"id" = "\d+"})
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param Job $job
     *
     * @return Response
     */
    public function edit(Request $request, EntityManagerInterface $em, Job $job, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logo')->getData();

            if ($logoFile instanceof UploadedFile) {
                $fileName = $fileUploader->upload($logoFile);

                $job->setLogo($fileName);
            }
            $em->flush();

            return $this->redirectToRoute('admin.job.list');
        }

        return $this->render('admin/job/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Delete job.
     *
     * @Route("/admin/job/{id}/delete", name="admin.job.delete", methods="DELETE", requirements={"id" = "\d+"})
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param Job $job
     *
     * @return Response
     */
    public function delete(Request $request, EntityManagerInterface $em, Job $job): Response
    {
        if ($this->isCsrfTokenValid('delete' . $job->getId(), $request->request->get('_token'))) {
            $em->remove($job);
            $em->flush();
        }

        return $this->redirectToRoute('admin.job.list');
    }
}
