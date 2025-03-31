<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class UpdateTaskController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/tasks/{id}', name: 'update_task', methods: ['PUT'])]
    public function updateTask(int $id, Request $request): JsonResponse
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
            return $this->json(['error' => 'Вы не можете редактировать эту задачу'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['title']) || empty($data['description']) || empty($data['status']) || empty($data['deadline'])) {
            return $this->json(['error' => 'Название, описание, статус и срок выполнения обязательны.'], 400);
        }

        $deadline = \DateTime::createFromFormat('Y-m-d H:i:s', $data['deadline']);
        if (!$deadline) {
            return $this->json(['error' => 'Неверный формат даты, должен быть Y-m-d H:i:s.'], 400);
        }

        $validStatuses = ['pending', 'in progress', 'completed', 'failed'];
        if (!in_array($data['status'], $validStatuses)) {
            return $this->json(['error' => 'Статус должен быть одним из следующих: "pending", "in progress", "completed", "failed".'], 400);
        }

        $task->setTitle($data['title'])
            ->setDescription($data['description'])
            ->setStatus($data['status'])
            ->setDeadline($deadline);

        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Ошибка при обновлении задачи: ' . $e->getMessage()], 500);
        }

        return $this->json([
            'message' => 'Задача успешно обновлена!',
            'task' => $this->taskToArray($task)
        ], 200);
    }

    private function taskToArray(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'deadline' => $task->getDeadline()->format('Y-m-d H:i:s'),
        ];
    }

}
