<?php

declare(strict_types=1);

namespace App\Domain\Shared\Event;

/**
 * Trait para agregados que necesitan publicar eventos de dominio. Guarda los
 * eventos en memoria con record() y los expone (vaciando el buffer) con
 * pullDomainEvents(), para que el CommandHandler los despache al event.bus
 * después de persistir.
 */
trait RecordsDomainEvents
{
    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    protected function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<DomainEvent> */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
