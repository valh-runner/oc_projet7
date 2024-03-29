<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"product:index", "product:read"})
     * 
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"product:index", "product:read"})
     */
    private ?string $model = null;

    /**
     * @ORM\Column(type="float")
     * @Groups("product:read")
     */
    private ?float $htPrice = null;

    /**
     * @ORM\Column(type="string", length=4)
     * @Groups("product:read")
     */
    private ?string $releaseYear = null;

    /**
     * @ORM\Column(type="smallint")
     * @Groups("product:read")
     */
    private ?int $weight = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("product:read")
     */
    private ?string $plateform = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("product:read")
     */
    private ?string $color = null;

    /**
     * @ORM\Column(type="float")
     * @Groups("product:read")
     */
    private ?float $screenSize = null;

    /**
     * @ORM\Column(type="smallint")
     * @Groups("product:read")
     */
    private ?int $storageSize = null;

    /**
     * @ORM\Column(type="smallint")
     * @Groups("product:read")
     */
    private ?int $ram = null;

    /**
     * @ORM\Column(type="smallint")
     * @Groups("product:read")
     */
    private ?int $coreNbr = null;

    /**
     * @ORM\Column(type="smallint")
     * @Groups("product:read")
     */
    private ?int $camMpx = null;

    /**
     * @ORM\Column(type="smallint")
     * @Groups("product:read")
     */
    private ?int $battery = null;

    /**
     * @ORM\ManyToOne(targetEntity=Brand::class, inversedBy="products")
     * @Groups({"product:index", "product:read"})
     */
    private ?\App\Entity\Brand $brand = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getHtPrice(): ?float
    {
        return $this->htPrice;
    }

    public function setHtPrice(float $htPrice): self
    {
        $this->htPrice = $htPrice;

        return $this;
    }

    public function getReleaseYear(): ?string
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(string $releaseYear): self
    {
        $this->releaseYear = $releaseYear;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getPlateform(): ?string
    {
        return $this->plateform;
    }

    public function setPlateform(string $plateform): self
    {
        $this->plateform = $plateform;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getScreenSize(): ?float
    {
        return $this->screenSize;
    }

    public function setScreenSize(float $screenSize): self
    {
        $this->screenSize = $screenSize;

        return $this;
    }

    public function getStorageSize(): ?int
    {
        return $this->storageSize;
    }

    public function setStorageSize(int $storageSize): self
    {
        $this->storageSize = $storageSize;

        return $this;
    }

    public function getRam(): ?int
    {
        return $this->ram;
    }

    public function setRam(int $ram): self
    {
        $this->ram = $ram;

        return $this;
    }

    public function getCoreNbr(): ?int
    {
        return $this->coreNbr;
    }

    public function setCoreNbr(int $coreNbr): self
    {
        $this->coreNbr = $coreNbr;

        return $this;
    }

    public function getCamMpx(): ?int
    {
        return $this->camMpx;
    }

    public function setCamMpx(int $camMpx): self
    {
        $this->camMpx = $camMpx;

        return $this;
    }

    public function getBattery(): ?int
    {
        return $this->battery;
    }

    public function setBattery(int $battery): self
    {
        $this->battery = $battery;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): self
    {
        $this->brand = $brand;

        return $this;
    }
}
