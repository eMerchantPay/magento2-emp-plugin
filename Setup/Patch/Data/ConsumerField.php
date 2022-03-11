<?php
/*
 * Copyright (C) 2022 emerchantpay Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      emerchantpay
 * @copyright   2022 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

namespace EMerchantPay\Genesis\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory as CustomerSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Create Data Migration with ConsumerId field assigned to the Magento 2 Customer
 */
class ConsumerField implements DataPatchInterface
{
    const CUSTOM_FIELD_CONSUMER_ID = 'consumer_id';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;
    private Config $eavConfig;
    private CustomerSetupFactory $customerSetupFactory;
    private AttributeSetFactory $attributeSetFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        Config $config,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $config;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * Patch Code
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet     = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Customer::ENTITY,
            self::CUSTOM_FIELD_CONSUMER_ID,
            [
                'label'      => self::CUSTOM_FIELD_CONSUMER_ID,
                'input'      => 'text',
                'visible'    => false,
                'required'   => false,
                'position'   => 150,
                'sort_order' => 150,
                'system'     => false
            ]
        );

        $document = $customerSetup->getEavConfig()
            ->getAttribute(Customer::ENTITY, self::CUSTOM_FIELD_CONSUMER_ID)
            ->addData([
                'attribute_set_id'   => $attributeSetId,
                'attribute_group_id' => $attributeGroupId
            ]);

        $document->save();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}
