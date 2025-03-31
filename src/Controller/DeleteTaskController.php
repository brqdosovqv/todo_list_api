<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class DeleteTaskController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/tasks/{id}', name: 'delete_task', methods: ['DELETE'])]
    public function deleteTask(int $id): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Пользователь не авторизован'], 401);
        }

        $task = $this->entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            return $this->json(['error' => 'Задача не найдена'], 404);
        }

        if ($task->getUser() !== $user) {
            return $this->json(['error' => 'Вы не можете удалить эту задачу'], 403);
        }

        try {
            $this->entityManager->remove($task);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Ошибка при удалении задачи: ' . $e->getMessage()], 500);
        }

        // Возвращаем успешный ответ
        return $this->json(['message' => 'Задача успешно удалена!'], 200);
    }
}
