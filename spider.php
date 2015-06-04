<?php

ini_set('max_execution_time', 0);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once './vendor/autoload.php';
require_once './error.php';

use Goutte\Client;

class Sleep
{
  private $count = 0;
  private $tc;

  public static function getInstance()
  {
    static $instance = null;
    if (null === $instance) {
      $instance = new static();
    }

    return $instance;
  }

  protected function __construct()
  {
    $this->tc = new TorControl\TorControl(
      array(
        'server' => '127.0.0.1',
        'port'   => 9051,
        'password' => 'test',
        'authmethod' => 1
      )
    );
  }

  private function __clone()
  {
  }

  private function __wakeup()
  {
  }

  public function sleep(){

    sleep(1);
    $this->count++;
    if($this->count > 20) {

      $this->tc->connect();

      $this->tc->authenticate();

// Renew identity
      $res = $this->tc->executeCommand('SIGNAL NEWNYM');

// Echo the server reply code and message
      echo $res[0]['code'].': '.$res[0]['message'];

// Quit
      $this->tc->quit();
      echo "new tor identity";
      sleep(10);
    }
  }

}

$poor_bastard = "http://www.azlyrics.com/";

$client = new Client();

$header=array(
  'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language: en-us,en;q=0.5',
        'Accept-Encoding: gzip,deflate',
	  'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
	    'Keep-Alive: 115',
	      'Connection: keep-alive',
	      );
	      
$client->getClient()->setDefaultOption('config/curl/'.CURLOPT_HTTPHEADER, $header);
$client->getClient()->setDefaultOption('config/curl/'.CURLOPT_PROXY, '52.24.15.87');
$client->getClient()->setDefaultOption('config/curl/'.CURLOPT_PROXYPORT, '3128');

$crawler = $client->request('GET', $poor_bastard);

$lyricsTxt = fopen("lyrics.txt", "w");

$crawler->filterXPath('//*[@id="artists-collapse"]/li/div/a')->each(function ($node) use ($client, $lyricsTxt) {

  $page = $client->click($node->link());
  
  $page->filterXPath('//html/body/div[2]/div/div/a')->each(function ($node) use ($client, $lyricsTxt) {

    $artist = $client->click($node->link());
    $artistDetails = $node->text();

    $artist->filterXPath('//*[@id="listAlbum"]/a[@target="_blank"]')->each(function ($node) use (
      $client,
      $artistDetails,
      $lyricsTxt
    ) {
      Sleep::getInstance()->sleep();

      $songTitle = $node->text();

      $song = $client->click($node->link());

      $text = $song->filterXPath('//html/body/div[3]/div/div[2]/div[6]')->text();

      $data = json_encode(array(
        "artist" => $artistDetails,
        "title" => $songTitle,
        "lyrics" => $text
       ));

       fwrite($lyricsTxt, $data);
       sleep(4);
       echo("\n\n" . $data);
    });

  });

});

fclose($lyricsTxt);
