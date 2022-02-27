# bitrix-dadata-geoip-handler
Обработчик геолокации Bitrix с использованием сервиса DaData


Для подключения обработчика используем событие модуля main, которое называется onMainGeoIpHandlersBuildList

Код подключения 
```
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler('main', 'onMainGeoIpHandlersBuildList', function()
    {
        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            [
                '\DaData' => '/local/DaData.php',
            ],
            'main'
        );
    });
```

Пример использования в своем коде
```php
// Получаем IP пользователя. Фактически метод получает параметры HTTP_X_FORWARDED_FOR или REMOTE_ADDR из $_SERVER 
$ipAddress = \Bitrix\Main\Service\GeoIp\Manager::getRealIp();

$locData = \Bitrix\Main\Service\GeoIp\Manager::getDataResult($ipAddress, LANGUAGE_ID);

$geoData = $locData->getGeoData();

/*
Примерный вывод
object(Bitrix\Main\Service\GeoIp\Data)#434 (16) {
  ["ip"]=>
  string(14) "83.239.206.206"
  ["lang"]=>
  string(2) "ru"
  ["countryName"]=>
  string(12) "Россия"
  ["regionName"]=>
  string(26) "Краснодарский"
  ["subRegionName"]=>
  NULL
  ["cityName"]=>
  string(8) "Сочи"
  ["countryCode"]=>
  string(2) "RU"
  ["regionCode"]=>
  string(6) "RU-KDA"
  ["zipCode"]=>
  NULL
  ["latitude"]=>
  string(9) "11.111111"
  ["longitude"]=>
  string(9) "11.111111"
  ["timezone"]=>
  NULL
  ["asn"]=>
  NULL
  ["ispName"]=>
  NULL
  ["organizationName"]=>
  NULL
  ["handlerClass"]=>
  string(25) "\DaData"
}
*/
```
