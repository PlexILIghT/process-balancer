<?php
namespace App\Controller;

use App\Entity\WorkerMachine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/machine')]
class WorkerMachineController extends AbstractController
{
    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $machine = new WorkerMachine();
        $machine->setTotalMemory($data['memory']);
        $machine->setTotalCpu($data['cpu']);

        if ($machine->getTotalMemory() <= 0 || $machine->getTotalCpu() <= 0) {
            return $this->json(['error' => 'Invalid resource values.'], 400);
        }

        $em->persist($machine);
        $em->flush();

        return $this->json(['id' => $machine->getId()], 201);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $machine = $em->getRepository(WorkerMachine::class)->find($id);
        if (!$machine) {
            return $this->json(['error' => 'Machine not found.'], 404);
        }

        $em->remove($machine);
        $em->flush();

        return $this->json(['status' => 'deleted']);
    }

    #[Route('', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $machines = $em->getRepository(WorkerMachine::class)->findAll();
        $data = array_map(fn(WorkerMachine $m) => [
            'id' => $m->getId(),
            'memory' => $m->getTotalMemory(),
            'cpu' => $m->getTotalCpu(),
        ], $machines);

        return $this->json($data);
    }
}