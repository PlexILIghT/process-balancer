<?php
namespace App\Controller;

use App\Entity\Process;
use App\Entity\WorkerMachine;
use App\Service\ProcessBalancer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/process')]
class ProcessController extends AbstractController
{
    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        ProcessBalancer $balancer,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $process = new Process();
        $process->setRequiredMemory($data['memory']);
        $process->setRequiredCpu($data['cpu']);

        // Валидация
        if ($process->getRequiredMemory() <= 0 || $process->getRequiredCpu() <= 0) {
            return $this->json(['error' => 'Invalid resource values.'], 400);
        }

        // Проверка наличия машин
        $machines = $em->getRepository(WorkerMachine::class)->findAll();
        if (empty($machines)) {
            return $this->json(['error' => 'No machines available.'], 400);
        }

        try {
            $balancer->assignProcess($process);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json(['id' => $process->getId()], 201);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $process = $em->getRepository(Process::class)->find($id);
        if (!$process) {
            return $this->json(['error' => 'Process not found.'], 404);
        }

        $em->remove($process);
        $em->flush();

        return $this->json(['status' => 'deleted']);
    }

    #[Route('', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $processes = $em->getRepository(Process::class)->findAll();
        $data = array_map(fn(Process $p) => [
            'id' => $p->getId(),
            'memory' => $p->getRequiredMemory(),
            'cpu' => $p->getRequiredCpu(),
            'machine_id' => $p->getWorkerMachine()?->getId(),
        ], $processes);

        return $this->json($data);
    }
}