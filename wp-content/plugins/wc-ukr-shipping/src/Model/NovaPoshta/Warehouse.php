<?php

namespace kirillbdev\WCUkrShipping\Model\NovaPoshta;

if ( ! defined('ABSPATH')) {
    exit;
}

class Warehouse
{
    public const TYPE_REGULAR = 1;
    public const TYPE_CARGO = 2;
    public const TYPE_POSHTOMAT = 3;

    private string $ref;
    private string $cityRef;
    private string $nameUa;
    private string $nameRu;
    private int $number;
    private int $type;
    private int $totalMaxWeight;
    private int $placeMaxWeight;

    public function __construct(
        string $ref,
        string $cityRef,
        string $nameRu,
        string $nameUa,
        int $number,
        int $type,
        int $totalMaxWeight,
        int $placeMaxWeight
    ) {
        $this->ref = $ref;
        $this->cityRef = $cityRef;
        $this->nameRu = $nameRu;
        $this->nameUa = $nameUa;
        $this->number = $number;
        $this->type = $type;
        $this->totalMaxWeight = $totalMaxWeight;
        $this->placeMaxWeight = $placeMaxWeight;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getCityRef(): string
    {
        return $this->cityRef;
    }

    public function getNameUa(): string
    {
        return $this->nameUa;
    }

    public function getNameRu(): string
    {
        return $this->nameRu;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTotalMaxWeight(): int
    {
        return $this->totalMaxWeight;
    }

    public function getPlaceMaxWeight(): int
    {
        return $this->placeMaxWeight;
    }
}
