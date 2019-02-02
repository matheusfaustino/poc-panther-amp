<?php

require __DIR__.'/vendor/autoload.php'; // Composer's autoloader

use Amp\Promise;

$pool = new \Amp\Parallel\Worker\DefaultPool(1);
$promises = [];

$urls = [
    'https://api-platform.com/',
    'https://www.google.com/',
    'https://amphp.org/packages',
];

// processo rodando a parte
//$process = new \Symfony\Component\Process\Process([
//    __DIR__.'/vendor/symfony/panther/chromedriver-bin/chromedriver_mac64',
//    '--headless',
//    'window-size=1200,1100',
//    '--disable-gpu',
//    '--port=9999',
//]);
//try {
//    $process->mustRun();
//
//    echo $process->getOutput();
//} catch (\Symfony\Component\Process\Exception\ProcessFailedException $exception) {
//    echo $exception->getMessage();
//}
//
//exit();

$defaultClient = new Amp\Artax\DefaultClient(
    null,
    null,
    (new \Amp\Socket\ClientTlsContext())->withoutPeerVerification()
);

foreach ($urls as $url) {
    var_dump($url);
    if ($url === 'https://api-platform.com/') {
        $promises[] = Amp\call(Amp\ParallelFunctions\parallel(function () use ($url) {
            print($url.PHP_EOL);
            $client = \Symfony\Component\Panther\Client::createChromeClient();
            $crawler = $client->request('GET', $url);
            $link = $crawler->selectLink('Support')->link();
            $crawler = $client->click($link);
            $client->waitFor('.support');

            return $crawler->filter('.support')->text();
        }, $pool));

        continue;
    }

    $promises[] = Amp\call(function (string $uri) use ($defaultClient) {
        print($uri.PHP_EOL);
        $response = yield $defaultClient->request($uri);

        return $response->getBody();
    }, $url);
}

try {
    $responses = Promise\wait(Promise\all($promises));
    var_dump($responses);
} catch (\Amp\MultiReasonException $exception) {
    var_dump($exception->getReasons());
}
