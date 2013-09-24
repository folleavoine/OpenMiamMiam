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

use Isics\Bundle\OpenMiamMiamUserBundle\Entity\User;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ConsumerManager
{
    /**
     * @var array $config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config)
    {
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
        $resolver->setRequired(array('ref_prefix', 'ref_pad_length'));
    }

    /**
     * Format consumer ref
     *
     * @param User $user User
     *
     * @return string ref
     */
    public function formatRef(User $user)
    {
        return sprintf(
            '%s%s',
            $this->config['ref_prefix'],
            str_pad($user->getId(), $this->config['ref_pad_length'], '0', STR_PAD_LEFT)
        );
    }
}