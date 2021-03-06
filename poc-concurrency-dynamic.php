<?php

require __DIR__.'/vendor/autoload.php'; // Composer's autoloader
const MAX_CONCURRENCY = 2;

$queue = new class
{
    /** @var array */
    private $queue;

    public function addItem(string $url): void
    {
        $this->queue[] = $url;
    }

    public function getQueue(): array
    {
        return $this->queue;
    }

    public function count(): int
    {
        return count($this->queue);
    }

    public function items(): \Generator
    {
        while ($url = array_pop($this->queue)) {
            yield $url;
        }
    }
};

$urls = [
    'https://www.google.com/',
    'https://api-platform.com/',
    'https://amphp.org/packages',
    'https://vuejsexamples.com/a-soundcloud-client-built-with-vue-js-2/',
    'https://github.com/SaifulAzam/muimp',
    'https://github.com/goldfire/howler.js',
    'https://muse-ui.org/#/en-US/slider',
];
foreach ($urls as $url) {
    $queue->addItem($url);
}

$pool = new \Amp\Parallel\Worker\DefaultPool(1);
$defaultClient = new Amp\Artax\DefaultClient(
    null,
    null,
    (new \Amp\Socket\ClientTlsContext())->withoutPeerVerification()
);
$defaultClient->setOption(\Amp\Artax\Client::OP_TRANSFER_TIMEOUT, 60 * 1000);

$requests = [];
$first = false;
foreach ($queue->items() as $url) {
    var_dump($url);
    if ($url === 'https://api-platform.com/') {
        $requests[] = Amp\call(Amp\ParallelFunctions\parallel(function () use ($url) {
            print($url.PHP_EOL);
            $client = \Symfony\Component\Panther\Client::createChromeClient();
            $crawler = $client->request('GET', $url);
            $link = $crawler->selectLink('Support')->link();
            $crawler = $client->click($link);
            $client->waitFor('.support');

            var_dump($crawler->filter('.support')->text());

            return $crawler->filter('.support')->text();
        }, $pool));

        continue;
    }

    if ($url === 'https://muse-ui.org/#/en-US/slider') {
        $requests[] = Amp\call(Amp\ParallelFunctions\parallel(function () use ($url) {
            print($url.PHP_EOL);
            $client = \Symfony\Component\Panther\Client::createChromeClient();
            $crawler = $client->request('GET', $url);
            $client->waitFor('.mu-appbar-title');
            $crawler = $client->getCrawler();

            var_dump($crawler->filter('.mu-appbar-title')->text());

            return $crawler->filter('.mu-appbar-title')->text();
        }, $pool));

        continue;
    }

    $requests[] = Amp\call(function (string $uri) use ($defaultClient, $queue, $first) {
//            print($uri);
        /* @var $response \Amp\Artax\Response */
        $response = yield $defaultClient->request($uri);

        /* @var $body Amp\ByteStream\Message */
        $body = yield $response->getBody();

        $crawler = new \Symfony\Component\DomCrawler\Crawler($body, $response->getOriginalRequest()->getUri());

        $url = $crawler->filterXPath('//a')->last()->link('get')->getUri();
        $first === false and stripos($url, 'http') !== false and $queue->addItem($url);
    }, $url);

    if ($queue->count() === 0 || count($requests) >= MAX_CONCURRENCY) {
        $first = true;
        var_dump('empty ot MAX_CONCURRENCY', $queue->getQueue());
        Amp\Promise\wait(Amp\Promise\all($requests));
    }
}
