<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class TaskShowController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/tasks', name: 'get_tasks', methods: ['GET'])]
    public function getTasks(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Пользователь не авторизован'], 401);
        }

        $tasks = $this->entityManager->getRepository(Task::class)->findBy(['user' => $user]);

        if (!$tasks) {
            return $this->json(['message' => 'Задачи не найдены'], 404);
        }

        $taskData = [];
        foreach ($tasks as $task) {
            $taskData[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'deadline' => $task->getDeadline()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json(['tasks' => $taskData], 200);
    }
}
