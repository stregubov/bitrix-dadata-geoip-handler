<?php

use Bitrix\Main\{Error, Result};
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Service\GeoIp\{Base, Data, ProvidingData};

final class DaData extends Base
{
    /**
     * @return string Title of handler.
     */
    public function getTitle()
    {
        return "DaData";
    }

    /**
     * @return string Handler description.
     */
    public function getDescription()
    {
        return "Определяет город по IP-адресу в России";
    }

    /**
     * @param string $ip Ip address
     * @param string $key license key.
     * @return Result
     */
    protected function sendRequest(string $ip, string $key)
    {
        $result = new Result();

        $httpClient = $this->getHttpClient();
        $httpClient->setHeader("Accept", "application/json");
        $httpClient->setHeader("Authorization", "Token $key");
        $url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address?ip=$ip";

        $httpRes = $httpClient->get($url);
        $errors = $httpClient->getError();

        if (!$httpRes && !empty($errors)) {
            $strError = "";

            foreach ($errors as $errorCode => $errMes) {
                $strError .= "$errorCode: $errMes";
            }

            $result->addError(new Error($strError));
        } else {
            $status = $httpClient->getStatus();

            if ($status != 200) {
                $result->addError(new Error("Dadata http status: $status"));
            } else {
                $arRes = json_decode($httpRes, true);

                if (is_array($arRes)) {
                    if (mb_strtolower(SITE_CHARSET) != "utf-8") {
                        $arRes = Encoding::convertEncoding($arRes, "UTF-8", SITE_CHARSET);
                    }

                    $result->setData($arRes);
                } else {
                    $result->addError(new Error("Can't decode json result"));
                }
            }
        }

        return $result;
    }

    /**
     * @return HttpClient
     */
    protected static function getHttpClient()
    {
        return new HttpClient([
            "version" => "1.1",
            "socketTimeout" => 5,
            "streamTimeout" => 5,
            "redirect" => true,
            "redirectMax" => 5,
        ]);
    }

    /**
     * Languages supported by handler ISO 639-1
     * @return array
     */
    public function getSupportedLanguages()
    {
        return ["ru"];
    }

    /**
     * @param string $ip Ip address
     * @param string $lang Language identifier
     * @return \Bitrix\Main\Service\GeoIp\Result | null
     */
    public function getDataResult($ip, $lang = "")
    {
        $dataResult = new \Bitrix\Main\Service\GeoIp\Result;
        $geoData = new Data();

        $geoData->ip = $ip;
        $geoData->lang = $lang = $lang != "" ? $lang : "en";
        $key = !empty($this->config["TOKEN"]) ? $this->config["TOKEN"] : "";
        $res = $this->sendRequest($ip, $key);

        if ($res->isSuccess()) {
            $data = $res->getData();

            $geoData->countryName = $data["location"]["data"]["country"];
            $geoData->countryCode = $data["location"]["data"]["country_iso_code"];
            $geoData->regionName = $data["location"]["data"]["region"];
            $geoData->regionCode = $data["location"]["data"]["region_iso_code"];
            $geoData->cityName = $data["location"]["data"]["city"];
            $geoData->latitude = $data["location"]["data"]["geo_lat"];
            $geoData->longitude = $data["location"]["data"]["geo_lon"];
        } else {
            $dataResult->addErrors($res->getErrors());
        }

        $dataResult->setGeoData($geoData);
        return $dataResult;
    }

    /**
     * @param array $postFields $_POST
     * @return array Field CONFIG for saving to DB in admin edit form.
     */
    public function createConfigField($postFields)
    {
        return ["TOKEN" => $postFields["TOKEN"] ?? ""];
    }

    /**
     * @return array Set of fields description for administration purposes.
     */
    public function getConfigForAdmin()
    {
        return [
            [
                "NAME" => "TOKEN",
                "TITLE" => "Токен",
                "TYPE" => "TEXT",
                "VALUE" => htmlspecialcharsbx($this->config["TOKEN"])
            ]
        ];
    }

    /**
     * @return ProvidingData Geolocation information witch handler can return.
     */
    public function getProvidingData()
    {
        $result = new ProvidingData();
        $result->countryName = true;
        $result->countryCode = true;
        $result->regionName = true;
        $result->regionCode = true;
        $result->cityName = true;
        $result->latitude = true;
        $result->longitude = true;
        return $result;
    }
}
