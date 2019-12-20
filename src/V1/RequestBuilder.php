<?php

namespace CloudFinance\EFattureWsClient\V1;


class RequestBuilder
{
    private $client;
    private $command;
    private $data;
    private $method;

    public function __construct(Client $client, $command, $data, $method) {
        $this->client = $client;
        $this->command = $command;
        $this->data = $data;
        $this->method = $method;
        $this->orderArray = [];
        $this->filterArray = [];

        $this->page(1);
        $this->perPage(10);
        $this->search("");
    }

    private $page;
    private $perPage;
    private $orderArray;
    private $filterArray;
    private $searchedText;

    public function page($page)
    {
        $this->page = $page;
        return $this;
    }

    public function perPage($perPage = 10)
    {
        $this->perPage = intval($perPage);
        return $this;
    }

    public function orderBy($column, $dir = "asc")
    {
        $this->orderArray[] = [
            "column" => $column,
            "dir" => $dir,
        ];
        return $this;
    }

    public function filterBy($filterName, $filterValue)
    {
        $this->filterArray[$filterName] = [
            "name" => $filterName,
            "value" => $filterValue,
        ];
        return $this;
    }

    public function search($searchedText)
    {
        $this->searchedText = $searchedText;
        return $this;
    }

    public function get()
    {
        $data = array_replace([
            "filters" => $this->filterArray,
            "order" => $this->orderArray,
            "page" => $this->page,
            "per_page" => $this->perPage,
            "search" => $this->searchedText,
        ], $this->data);

        return $this->client->executeHttpRequest(
            $this->command, $data, $this->method
        );
    }

}
