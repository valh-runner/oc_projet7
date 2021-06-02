<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $brandsDataset = ['Apple', 'Samsung', 'Huawei', 'Xiaomi'];

        $productsDataset = [
            ['iPhone 12 - 256',               '816',  '2020', '162', 'iOS 14',     0, 'noir',     '6.1', '256', '4', '6', '12', '2815'],
            ['iPhone 12 Pro - 512',           '1200', '2020', '187', 'iOS 14',     0, 'graphite', '6.1', '512', '6', '6', '12', '2815'],
            ['iPhone 12 Pro Max - 512',       '1280', '2020', '226', 'iOS 14',     0, 'graphite', '6.7', '512', '6', '6', '12', '3687'],
            ['iPhone 12 Mini - 256',          '736',  '2020', '133', 'iOS 14',     0, 'noir',     '5.4', '256', '4', '6', '12', '2227'],
            ['iPhone 11 - 256',               '688',  '2019', '194', 'iOS 13',     0, 'noir',     '6.1', '256', '4', '6', '12', '3110'],
            ['iPhone SE - 256',               '528',  '2020', '148', 'iOS 13',     0, 'noir',     '4.7', '256', '3', '6', '12', '1821'],
            ['iPhone XR - 128',               '512',  '2018', '194', 'iOS 12',     0, 'noir',     '6.1', '128', '3', '6', '12', '2942'],
            ['S21 Ultra 5G - 512 Go - 16 Go', '1152', '2021', '228', 'Android 11', 1, 'noir',     '6.8', '512', '16', '8', '108', '5000'],
            ['S21+ 5G - 256 Go - 8 Go',       '880',  '2021', '202', 'Android 11', 1, 'noir',     '6.7', '256', '8', '8', '108', '4800'],
            ['S21 5G - 256 Go - 8 Go',        '728',  '2021', '171', 'Android 11', 1, 'gris',     '6.2', '256', '8', '8', '108', '4000'],
            ['S20 FE G781 5G',                '608',  '2020', '193', 'Android 10', 1, 'bleu',     '6.5', '128', '6', '8', '108', '4500'],
            ['Samsung Galaxy A52 5G',         '320',  '2021', '189', 'Android 11', 1, 'noir',     '6.5', '128', '6', '8', '64', '4500'],
            ['P40 Pro 5G',                    '600',  '2020', '203', 'Android 10', 2, 'noir',     '6.5', '256', '8', '8', '50', '4200'],
            ['P40 5G',                        '360',  '2020', '175', 'Android 10', 2, 'noir',     '6.1', '128', '8', '8', '50', '3800'],
            ['Mi 11 5G',                      '640',  '2021', '196', 'Android 11', 3, 'bleu',     '6.8', '256', '8', '8', '108', '4600'],
            ['Mi 11i 5G',                     '560',  '2021', '196', 'Android 11', 3, 'noir',     '6.6', '256', '8', '8', '108', '4520']
        ];

        // Create brands
        $nbrBrands = count($brandsDataset);
        for ($j = 0; $j < $nbrBrands; $j++) {
            $brand = new Brand();
            $brand->setName($brandsDataset[$j]);
            $manager->persist($brand);

            // Create products
            $nbrProducts = count($productsDataset);
            for ($k = 0; $k < $nbrProducts; $k++) {
                $productData = $productsDataset[$k];

                //If the product correspond to the current brand
                if ($productData[5] == $j) {
                    $product = new Product();
                    $product->setModel($productData[0])
                        ->setHtPrice($productData[1])
                        ->setReleaseYear($productData[2])
                        ->setWeight($productData[3])
                        ->setPlateform($productData[4])
                        ->setColor($productData[6])
                        ->setScreenSize(floatval($productData[7]))
                        ->setStorageSize($productData[8])
                        ->setRam($productData[9])
                        ->setCoreNbr($productData[10])
                        ->setCamMpx($productData[11])
                        ->setBattery($productData[12])
                        ->setBrand($brand);
                    $manager->persist($product);
                }
            }
        }

        $manager->flush();
    }
}
