<?php

declare(strict_types=1);

namespace App\Tests\Integration\Search\Command;

use App\Dto\Product\Search;
use App\Search\Command\IndexProductsCommand;
use App\Search\Meilisearch;
use App\Test\ContainerRepositoryTrait;
use App\Tests\TestReference;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class IndexProductsCommandTest extends KernelTestCase
{
    use ContainerRepositoryTrait;

    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find(IndexProductsCommand::CMD);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        self::assertStringContainsString(\sprintf('%d product(s) indexed', TestReference::PRODUCTS_INDEXABLE_COUNT), $output);

        // check if the search is OK
        // sleep(2); // wait a little to be sure the indexing process is OK
        /** @var Meilisearch $meilisearch */
        $meilisearch = self::getContainer()->get(Meilisearch::class);
        $client = $meilisearch->getClient();

        // Wait for Meilisearch to be ready
        $tasks = $client->getTasks();
        $uids = array_column($tasks->toArray(), 'uid');
        $client->waitForTasks($uids);

        $infos = $client->getIndex('products');
        self::assertSame('products', $infos->getUid());

        $searchDto = new Search('');

        // all documents when not logged (-1 because of a restricted product)
        // services are now restricted and do not appear here with default filters
        $results = $meilisearch->search($searchDto);
        self::assertSame(TestReference::PRODUCTS_VISIBLE_COUNT - 1, $results->getHitsCount());

        // all documents when logged with a user with access to the restricted product
        $searchDto->user = $this->getUserRepository()->get(TestReference::PLACE_APES);
        $results = $meilisearch->search($searchDto);
        self::assertSame(TestReference::PRODUCTS_VISIBLE_COUNT, $results->getHitsCount());

        // keyword search
        $searchDto->user = null;
        $searchDto->q = 'vélo';
        $results = $meilisearch->search($searchDto);
        self::assertSame(3, $results->getHitsCount());

        // typo tolerance example
        $searchDto->q = 'jumeles';
        $results = $meilisearch->search($searchDto);
        self::assertSame(1, $results->getHitsCount());

        // test vacation

        $searchDto->user = null;
        $searchDto->q = '';
        $user16 = $this->getUserRepository()->get(TestReference::USER_16);
        $user16->setVacationMode(true);
        $this->getUserManager()->save($user16);
        $results = $meilisearch->search($searchDto);
        // two objects are now hidden
        self::assertSame(TestReference::PRODUCTS_VISIBLE_COUNT - 3, $results->getHitsCount());
    }
}
