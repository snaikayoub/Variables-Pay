<?php

namespace App\Filter;

use App\Entity\Service;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use Symfony\Contracts\Translation\TranslatableInterface;

final class CurrentServiceFilter implements FilterInterface
{
    use FilterTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName = 'service', $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(EntityFilterType::class)
            ->setFormTypeOption('value_type_options.class', Service::class)
            ->setFormTypeOption('value_type_options.choice_label', 'nom');
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $alias = $filterDataDto->getEntityAlias();
        $comparison = $filterDataDto->getComparison();
        $parameterName = $filterDataDto->getParameterName();
        $value = $filterDataDto->getValue();

        if (null === $value) {
            return;
        }

        $situationAlias = 'ea_es_'.$parameterName;
        $serviceAlias = 'ea_srv_'.$parameterName;
        $todayParam = $parameterName.'_today';

        $queryBuilder
            ->distinct()
            ->leftJoin(sprintf('%s.employeeSituations', $alias), $situationAlias)
            ->leftJoin(sprintf('%s.service', $situationAlias), $serviceAlias)
            ->andWhere(sprintf('%s.startDate <= :%s', $situationAlias, $todayParam))
            ->andWhere(sprintf('(%s.endDate IS NULL OR %s.endDate >= :%s)', $situationAlias, $situationAlias, $todayParam))
            ->andWhere(sprintf('%s %s (:%s)', $serviceAlias, $comparison, $parameterName))
            ->setParameter($todayParam, new \DateTimeImmutable('today'))
            ->setParameter($parameterName, $value);
    }
}
