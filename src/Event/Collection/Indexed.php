<?php

/*
 * This file is part of CalendR, a Fréquence web project.
 *
 * (c) 2012 Fréquence web
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CalendR\Event\Collection;

use CalendR\Event\EventInterface;
use CalendR\Period\PeriodInterface;

/**
 * This class store event by indexing them with a given index pattern.
 * Index pattern is generated by an index function.
 *
 * @author Yohan Giarelli <yohan@giarel.li>
 */
class Indexed implements CollectionInterface
{
    /**
     * @var EventInterface[][]
     */
    protected $events;

    /**
     * Event count.
     */
    protected $count = 0;

    /**
     * The function used to index events.
     * Takes a \DateTime in parameter and must return an array index for this value.
     *
     * By default :
     * ```php
     *  function(\DateTime $dateTime) {
     *      return $dateTime->format('Y-m-d');
     *  }
     * ```
     *
     * @var callable
     */
    protected $indexFunction;

    /**
     * @param array<EventInterface> $events
     * @param callable|null $callable
     */
    public function __construct(array $events = [], ?callable $callable = null)
    {
        if (is_callable($callable)) {
            $this->indexFunction = $callable;
        } else {
            $this->indexFunction = static function (\DateTimeInterface $dateTime) {
                return $dateTime->format('Y-m-d');
            };
        }

        foreach ($events as $event) {
            $this->add($event);
        }
    }

    /**
     * Adds an event to the collection.
     */
    public function add(EventInterface $event): void
    {
        $index = $this->computeIndex($event);
        if (isset($this->events[$index])) {
            $this->events[$index][] = $event;
        } else {
            $this->events[$index] = [$event];
        }

        ++$this->count;
    }

    /**
     * Removes an event from the collection.
     */
    public function remove(EventInterface $event): void
    {
        $index = $this->computeIndex($event);
        if (isset($this->events[$index])) {
            foreach ($this->events[$index] as $key => $internalEvent) {
                if ($event->getUid() === $internalEvent->getUid()) {
                    unset($this->events[$index][$key]);
                    --$this->count;
                }
            }
        }
    }

    /**
     * Returns if we have events for the given index.
     */
    public function has($index): bool
    {
        return 0 < count($this->find($index));
    }

    /**
     * returns events.
     *
     * @param mixed $index
     *
     * @return EventInterface[]
     */
    public function find($index): array
    {
        if ($index instanceof PeriodInterface) {
            $index = $index->getBegin();
        }
        if ($index instanceof \DateTime) {
            $index = $this->computeIndex($index);
        }

        return $this->events[$index] ?? [];
    }

    /**
     * Returns a flattened array of all events.
     *
     * @return EventInterface[]
     */
    public function all(): array
    {
        $results = [];

        foreach ($this->events as $events) {
            $results = array_merge($results, $events);
        }

        return $results;
    }

    /**
     * Computes event index.
     *
     * @param EventInterface|\DateTimeInterface $toCompute
     */
    protected function computeIndex($toCompute): string
    {
        if ($toCompute instanceof EventInterface) {
            $toCompute = $toCompute->getBegin();
        }
        $function = $this->indexFunction;

        return $function($toCompute);
    }

    public function count(): int
    {
        return $this->count;
    }
}
