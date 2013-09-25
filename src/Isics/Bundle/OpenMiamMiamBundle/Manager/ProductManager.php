<?php

/*
 * This file is part of the OpenMiamMiam project.
 *
 * (c) Isics <contact@isics.fr>
 *
 * This source file is subject to the AGPL v3 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Isics\Bundle\OpenMiamMiamBundle\Manager;

use Doctrine\ORM\EntityManager;
use Isics\Bundle\OpenMiamMiamBundle\Entity\Branch;
use Isics\Bundle\OpenMiamMiamBundle\Entity\Category;
use Isics\Bundle\OpenMiamMiamBundle\Entity\Producer;
use Isics\Bundle\OpenMiamMiamBundle\Entity\Product;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ProductManager
 *
 * @package Isics\Bundle\OpenMiamMiamBundle\Manager
 */
class ProductManager
{
    /**
     * @var EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * @var KernelInterface $kernel
     */
    protected $kernel;

    /**
     * @var array $config
     */
    protected $config;



    /**
     * Constructs object
     *
     * @param array $config
     * @param EntityManager $entityManager
     * @param KernelInterface $kernel
     */
    public function __construct(array $config, EntityManager $entityManager, KernelInterface $kernel)
    {
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;

        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->config = $resolver->resolve($config);
    }

    /**
     * Set the defaults options
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('ref_prefix', 'ref_pad_length', 'upload_path'));
    }

    /**
     * Returns a new product for a producer
     *
     * @param Producer $producer
     *
     * @return Product
     */
    public function createForProducer(Producer $producer)
    {
        $product = new Product();
        $product->setProducer($producer);

        // Init product reference
        $product->setRef(sprintf(
            '%s%s',
            $this->config['ref_prefix'],
            str_pad($producer->getProductRefCounter()+1, $this->config['ref_pad_length'], '0', STR_PAD_LEFT)
        ));

        // Select all producer branches
        $product->setBranches($producer->getBranches());

        return $product;
    }

    /**
     * Saves a product
     *
     * @param Product $product
     */
    public function save(Product $product)
    {
        // Save object
        $this->entityManager->persist($product);

        // Increase producer product reference counter
        if (null === $product->getId()) {
            $producer = $product->getProducer();
            $producer->setProductRefCounter($producer->getProductRefCounter()+1);
            $this->entityManager->persist($producer);
        }

        $this->entityManager->flush();

        // Process image file
        $this->processImageFile($product);
    }

    /**
     * Deletes a product
     *
     * @param Product $product
     */
    public function delete(Product $product)
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    /**
     * Returns image path
     *
     * @param Product $product
     *
     * @return string
     */
    public function getImagePath(Product $product)
    {
        if (null === $product->getImage()) {
            return null;
        }

        return $this->getUploadDir($product).'/'.$product->getImage();
    }

    /**
     * Returns upload directory
     *
     * @param Product $product
     *
     * @throws \DomainException
     *
     * @return string
     */
    public function getUploadDir(Product $product)
    {
        $producer = $product->getProducer();
        if (!$producer instanceof Producer) {
            throw new \DomainException();
        }

        return $this->config['upload_path'].'/'.$producer->getId();
    }

    /**
     * Processes image file
     *
     * @param Product $product
     */
    public function processImageFile(Product $product)
    {
        // Delete image if flag is true
        if (null !== $product->getImage() && $product->getDeleteImage()) {
            return $this->removeImage($product);
        }
        // Move new image
        elseif (null !== $product->getImageFile()) {
            $this->uploadImage($product);
        }
    }

    /**
     * Removes image file
     *
     * @param Product $product
     */
    public function removeImage(Product $product)
    {
        $fileSystem = new Filesystem();
        $uploadDir = $this->kernel->getRootDir().'/../web'.$this->getUploadDir($product);

        $fileSystem->remove($uploadDir.'/'.$product->getImage());

        $product->setImage(null);

        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    /**
     * Uploads image file
     *
     * @param Product $product
     */
    public function uploadImage(Product $product)
    {
        $fileSystem = new Filesystem();
        $uploadDir = $this->kernel->getRootDir().'/../web'.$this->getUploadDir($product);

        // Remove old image
        if (null !== $product->getImage()) {
            $fileSystem->remove($uploadDir.'/'.$product->getImage());
        }

        // Move image
        $file = $product->getImageFile();
        $filename = uniqid($product->getSlug()).'.'.$file->guessExtension();
        $file->move($uploadDir, $filename);

        // Set new image filename and reset image file
        $product->setImage($filename);
        $product->setImageFile(null);

        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    /**
     * Returns visible products for branch and category
     *
     * @param Branch $branch
     * @param Category $category
     *
     * @return array
     */
    public function getProductsToDisplay(Branch $branch, Category $category)
    {
        // TODO stock products to display in a new member of category : $productsToDisplay[branch][category]
        return $this->entityManager->getRepository('IsicsOpenMiamMiamBundle:Product')->findAllVisibleInBranchAndCategory(
            $branch,
            $category
        );
    }
}
