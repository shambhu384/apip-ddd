<?php

declare(strict_types=1);

namespace App\Infrastructure\BookStore\ApiPlatform\State\Processor;

use ApiPlatform\State\ProcessorInterface;
use App\Application\BookStore\Command\CreateBookCommand;
use App\Application\BookStore\Command\DeleteBookCommand;
use App\Application\BookStore\Command\UpdateBookCommand;
use App\Application\Shared\Command\CommandBusInterface;
use App\Domain\BookStore\Model\Book;
use App\Infrastructure\BookStore\ApiPlatform\Resource\BookResource;
use Symfony\Component\Uid\Uuid;

final class BookCrudProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function supports($data, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        return isset($context['operation']) && BookResource::class === $context['operation']->getClass();
    }

    /**
     * @param BookResource $data
     */
    public function process($data, array $identifiers = [], ?string $operationName = null, array $context = []): ?BookResource
    {
        if ($context['operation']->isDelete()) {
            $this->commandBus->dispatch(new DeleteBookCommand(Uuid::fromString($identifiers['id'])));

            return null;
        }

        $command = !isset($identifiers['id'])
            ? new CreateBookCommand($data->name, $data->description, $data->author, $data->content, $data->price)
            : new UpdateBookCommand(Uuid::fromString($identifiers['id']), $data->name, $data->description, $data->author, $data->content, $data->price)
        ;

        /** @var Book $model */
        $model = $this->commandBus->dispatch($command);

        return BookResource::fromModel($model);
    }
}