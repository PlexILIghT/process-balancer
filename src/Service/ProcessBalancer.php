<?php
namespace App\Service;

use App\Entity\Process;
use App\Entity\WorkerMachine;
use Doctrine\ORM\EntityManagerInterface;

class ProcessBalancer
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function assignProcess(Process $process): void
    {

        if ($process->getRequiredMemory() <= 0 || $process->getRequiredCpu() <= 0) {
            throw new \InvalidArgumentException(
                'Required memory and CPU must be positive integers. Got memory: '.
                $process->getRequiredMemory().', CPU: '.$process->getRequiredCpu()
            );
        }

        $machines = $this->em->getRepository(WorkerMachine::class)->findAll();
        $bestMachine = null;
        $minLoad = PHP_INT_MAX;

        foreach ($machines as $machine) {
            $usedMemory = $this->calculateUsedMemory($machine);
            $usedCpu = $this->calculateUsedCpu($machine);

            $freeMemory = $machine->getTotalMemory() - $usedMemory;
            $freeCpu = $machine->getTotalCpu() - $usedCpu;

            if ($freeMemory >= $process->getRequiredMemory()
                && $freeCpu >= $process->getRequiredCpu()
            ) {
                $currentLoad = ($usedMemory + $process->getRequiredMemory()) / $machine->getTotalMemory()
                    + ($usedCpu + $process->getRequiredCpu()) / $machine->getTotalCpu();

                if ($currentLoad < $minLoad) {
                    $bestMachine = $machine;
                    $minLoad = $currentLoad;
                }
            }
        }

        if (!$bestMachine) {
            throw new \RuntimeException('No available machine with enough resources.');
        }

        $process->setWorkerMachine($bestMachine);
        $this->em->persist($process);
        $this->em->flush();
    }

    private function calculateUsedMemory(WorkerMachine $machine): int
    {
        return array_sum(
            $machine->getProcesses()->map( fn(Process $p) => $p->getRequiredMemory())->toArray()
        );
    }

    private function calculateUsedCpu(WorkerMachine $machine): int
    {
        return array_sum(
            $machine->getProcesses()->map( fn(Process $p) => $p->getRequiredCpu())->toArray()
        );
    }

    public function rebalance(): void
    {
        $processes = $this->em->getRepository(Process::class)->findAll();
        foreach ($processes as $process) {
            // Отвязываем процесс от машины
            $process->setWorkerMachine(null);
            $this->em->persist($process);
            $this->em->flush();

            // Пытаемся разместить заново
            $this->assignProcess($process);
        }
    }
}