<?php

namespace App\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Symfony\Contracts\Translation\TranslatableInterface;

final class CurrentTypePaieFilter implements FilterInterface
{
    use FilterTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName = 'typePaie', $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceFilterType::class)
            ->setFormTypeOption('value_type_options.choices', [
                'Mensuelle' => 'mensuelle',
                'Quinzaine' => 'quinzaine',
            ])
            ->setFormTypeOption('value_type_options.placeholder', 'Tous');
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $alias = $filterDataDto->getEntityAlias();
        $comparison = $filterDataDto->getComparison();
        $parameterName = $filterDataDto->getParameterName();
        $value = $filterDataDto->getValue();
        $isMultiple = (bool) $filterDataDto->getFormTypeOption('value_type_options.multiple');

        $situationAlias = 'ea_es_'.$parameterName;
        $todayParam = $parameterName.'_today';

        $queryBuilder
            ->distinct()
            ->leftJoin(sprintf('%s.employeeSituations', $alias), $situationAlias)
            ->andWhere(sprintf('%s.startDate <= :%s', $situationAlias, $todayParam))
            ->andWhere(sprintf('(%s.endDate IS NULL OR %s.endDate >= :%s)', $situationAlias, $situationAlias, $todayParam))
            ->setParameter($todayParam, new \DateTimeImmutable('today'));

        if (null === $value || ($isMultiple && 0 === \count($value))) {
            $queryBuilder->andWhere(sprintf('%s.type_paie %s', $situationAlias, $comparison));

            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s.type_paie %s (:%s)', $situationAlias, $comparison, $parameterName))
            ->setParameter($parameterName, $value);
    }
}
