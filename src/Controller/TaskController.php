<?php

namespace App\Controller;

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/task")
 */
class TaskController extends AbstractController
{
    /**
     * @Route("/", name="task_index", methods={"GET"})
     */
    public function index(TaskRepository $taskRepository, Request $request): Response
    {
        return $this->render('task/index.html.twig');
    }

    /**
     * @Route("/api/task_search", name="task_search", methods={"GET"})
     */
    public function search(TaskRepository $taskRepository, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));

        $statusFilter = $request->query->get('status', '') === 'none' ? null : $request->query->get('status');

        $filters = [
            'title' => $request->query->get('title', ''),
            'dueDate' => $request->query->get('dueDate', ''),
            'status' => $statusFilter,
        ];

        $sortField = $request->query->get('sortField', 'id');
        $sortDirection = strtoupper($request->query->get('sortDirection', 'ASC'));

        if (!in_array($sortDirection, ['ASC', 'DESC'])) {
            $sortDirection = 'ASC';
        }

        $paginationData = $taskRepository->searchByFiltersWithPagination($filters, $sortField, $sortDirection, $page, $limit);

        return new JsonResponse([
            'tasks' => array_map(function ($task) {
                return [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'status' => $task->getStatus(),
                    'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d H:i') : 'N/A',
                    'image' => $task->getImage() ? $task->getImage() : null,
                ];
            }, $paginationData['tasks']),
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $paginationData['totalPages'],
                'totalItems' => $paginationData['totalItems'],
                'limit' => $limit,
            ],
        ]);
    }


    /**
     * @Route("/new", name="task_new")
     */
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('task_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    dd($e->getMessage());
                }

                $task->setImage($newFilename);
            }

            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="task_delete")
     */
    public function delete($id, EntityManagerInterface $entityManager): Response
    {

        $task = $entityManager->getRepository(Task::class)->find($id);
        if ($task) {
            $entityManager->remove($task);
            $entityManager->flush();

            $this->addFlash('success', 'Task deleted successfully');
        } else {
            $this->addFlash('danger', 'An error occurred while deleting the task');
        }

        return $this->redirectToRoute('task_index');
    }

    /**
     * @Route("/edit/{id}", name="task_edit")
     */
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            $this->addFlash('error', 'Task not found!');
            return $this->redirectToRoute('task_index');
        }

        $oldImage = $task->getImage();
        $task->imageFileName = $oldImage;

        if ($oldImage) {
            $oldImagePath = $this->getParameter('task_images_directory') . '/' . $oldImage;
            if (file_exists($oldImagePath)) {
                $task->setImage(new File($oldImagePath));
            }
        }

        $form = $this->createForm(TaskType::class, $task, [
            'allow_extra_fields' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            if ($image) {
                // Generate a new unique filename
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('task_images_directory'),
                        $newFilename
                    );

                    // Delete old image if exists
                    if ($oldImage) {
                        $oldImagePath = $this->getParameter('task_images_directory') . '/' . $oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
                    return $this->redirectToRoute('task_edit', ['id' => $id]);
                }

                $task->setImage($newFilename);
            } else {
                $task->setImage($oldImage);
            }

            $entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }


    /**
     * @Route("/duplicate/{id}", name="task_duplicate")
     */
    public function duplicate($id, EntityManagerInterface $entityManager): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            $this->addFlash('error', 'Task not found!');
            return $this->redirectToRoute('task_index');
        }

        $duplicateTask = clone $task;
        $entityManager->persist($duplicateTask);
        $entityManager->flush();

        return $this->redirectToRoute('task_index');
    }
}
