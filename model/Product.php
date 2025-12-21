<?php
require_once __DIR__ . '/config.php';

class Product
{
    private $id;
    private $publisher_id;
    private $name;
    private $description;
    private $price;
    private $stock;
    private $category;
    private $brand;
    private $image;
    private $created_at;
    private $updated_at;

    public function __construct($id = null, $publisher_id = null, $name = null, $description = null, $price = null, $stock = null, $category = null, $brand = null, $image = null, $created_at = null, $updated_at = null)
    {
        $this->id = $id;
        $this->publisher_id = $publisher_id;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->stock = $stock;
        $this->category = $category;
        $this->brand = $brand;
        $this->image = $image;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }
    public function getPublisherId()
    {
        return $this->publisher_id;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function getPrice()
    {
        return $this->price;
    }
    public function getStock()
    {
        return $this->stock;
    }
    public function getCategory()
    {
        return $this->category;
    }
    public function getBrand()
    {
        return $this->brand;
    }
    public function getImage()
    {
        return $this->image;
    }
    public function getCreatedAt()
    {
        return $this->created_at;
    }
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    // Setters
    public function setId($id)
    {
        $this->id = $id;
    }
    public function setPublisherId($publisher_id)
    {
        $this->publisher_id = $publisher_id;
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function setDescription($description)
    {
        $this->description = $description;
    }
    public function setPrice($price)
    {
        $this->price = $price;
    }
    public function setStock($stock)
    {
        $this->stock = $stock;
    }
    public function setCategory($category)
    {
        $this->category = $category;
    }
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }
    public function setImage($image)
    {
        $this->image = $image;
    }
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
    }
}
?>