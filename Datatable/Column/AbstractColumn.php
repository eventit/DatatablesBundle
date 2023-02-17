<?php

/*
 * This file is part of the SgDatatablesBundle package.
 *
 * (c) stwe <https://github.com/stwe/DatatablesBundle>
 * (c) event it AG <https://github.com/eventit/DatatablesBundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sg\DatatablesBundle\Datatable\Column;

use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use JsonException;
use RuntimeException;
use Sg\DatatablesBundle\Datatable\AddIfTrait;
use Sg\DatatablesBundle\Datatable\OptionsTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment as Twig_Environment;

abstract class AbstractColumn implements ColumnInterface
{
    // Use an 'add_if' option to check in ColumnBuilder if the Column can be added.
    use AddIfTrait;

    use OptionsTrait;

    // -------------------------------------------------
    // Column Types
    // -------------------------------------------------

    /**
     * Identifies a Data Column.
     */
    public const DATA_COLUMN = 'data';

    /**
     * Identifies an Action Column.
     */
    public const ACTION_COLUMN = 'action';

    /**
     * Identifies a Multiselect Column.
     */
    public const MULTISELECT_COLUMN = 'multiselect';

    /**
     * Identifies a Virtual Column.
     */
    public const VIRTUAL_COLUMN = 'virtual';

    // --------------------------------------------------------------------------------------------------
    // DataTables - Columns Options
    // ----------------------------
    // All Column Options are initialized with 'null' - except 'searchable', 'orderable', and 'visible'.
    // These 'null' initialized options uses the default value of the DataTables plugin.
    // 'searchable', 'orderable', and 'visible' are required in the QueryBuilder and are therefore
    // pre-assigned with a value (true or false).
    // --------------------------------------------------------------------------------------------------

    /**
     * Change the cell type created for the column - either TD cells or TH cells.
     * DataTables default: td
     * Default: null.
     */
    protected ?string $cellType = null;

    /**
     * Adds a class to each cell in a column.
     * Default: null.
     */
    protected ?string $className = null;

    /**
     * Add padding to the text content used when calculating the optimal with for a table.
     * Default: null.
     */
    protected ?string $contentPadding = null;

    /**
     * Set the data source for the column from the rows data object / array.
     * DataTables default: Takes the index value of the column automatically.
     *
     * This property has normally the same value as $this->dql.
     * Is set in the ColumnBuilder.
     */
    protected mixed $data = null;

    /**
     * Set default, static, content for a column.
     * Show an information message for a field that can have a 'null' or 'undefined' value.
     * Default: null.
     */
    protected mixed $defaultContent = null;

    /**
     * Set a descriptive name for a column. Only needed when working with DataTables' API.
     * Default: null.
     */
    protected ?string $name = null;

    /**
     * Enable or disable ordering on this column.
     * DataTables default: true
     * Default: true.
     */
    protected bool $orderable = true;

    /**
     * Define multiple column ordering as the default order for a column.
     * DataTables default: Takes the index value of the column automatically.
     * Default: null.
     */
    protected array|int|null $orderData = null;

    /**
     * Order direction application sequence.
     * DataTables default: ['asc', 'desc']
     * Default: null.
     */
    protected ?array $orderSequence = null;

    /**
     * Enable or disable filtering on the data in this column.
     * DataTables default: true
     * Default: true.
     */
    protected bool $searchable = true;

    /**
     * Set the column title.
     * DataTables default: Value read from the column's header cell.
     * Default: null.
     */
    protected ?string $title = null;

    /**
     * Enable or disable the display of this column.
     * DataTables default: true
     * Default: true.
     */
    protected bool $visible = true;

    /**
     * Column width assignment.
     * DataTables default: Auto-detected from the table's content.
     * Default: null.
     */
    protected ?string $width = null;

    // -------------------------------------------------
    // Custom Options
    // -------------------------------------------------

    /**
     * Join type (default: 'leftJoin'), if the column represents an association.
     * Default: 'leftJoin'.
     */
    protected string $joinType = 'leftJoin';

    /**
     * The data type of the column.
     * Is set automatically in ColumnBuilder when 'null'.
     * Default: null.
     */
    protected ?string $typeOfField = null;

    /**
     * The first argument of ColumnBuilders 'add' function.
     * The DatatableQuery class works with this property.
     * If $dql is used as an option, the ColumnBuilder sets $customDql to true.
     */
    protected ?string $dql = null;

    // -------------------------------------------------
    // Extensions Options
    // -------------------------------------------------

    /**
     * Set column's visibility priority.
     * Requires the Responsive extension.
     * Default: null.
     */
    protected ?int $responsivePriority = null;

    // -------------------------------------------------
    // Other Properties
    // -------------------------------------------------

    /**
     * True if DQL option is provided.
     * Is set in the ColumnBuilder.
     */
    protected bool $customDql = false;

    /**
     * The Twig Environment to render Twig templates in Column rowes.
     * Is set in the ColumnBuilder.
     */
    protected ?Twig_Environment $twig = null;

    /**
     * The Router.
     * Is set in the ColumnBuilder.
     */
    protected ?RouterInterface $router = null;

    /**
     * The position in the Columns array.
     * Is set in the ColumnBuilder.
     */
    protected int $index = 0;

    /**
     * The name of the associated Datatable.
     * Is set in the ColumnBuilder.
     */
    protected string $datatableName = '';

    /**
     * The fully-qualified class name of the entity (e.g. AppBundle\Entity\Post).
     * Is set in the ColumnBuilder.
     */
    protected string $entityClassName = '';

    /**
     * The type of association.
     * Is set in the ColumnBuilder.
     */
    protected ?array $typeOfAssociation = null;

    /**
     * Saves the original type of field for the DatatableController editAction.
     * Is set in the ColumnBuilder.
     */
    protected ?string $originalTypeOfField = null;

    /**
     * If the field is sent in the response, to show in the webpage
     * Is set in the ColumnBuilder.
     * Default: true.
     */
    protected bool $sentInResponse = true;

    /**
     * The group name of search columns to look at in a column search.
     */
    protected ?string $searchColumnGroup = null;

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    public function configureOptions(OptionsResolver $resolver): static
    {
        // 'dql' and 'data' options need no default value
        $resolver->setDefined(['dql', 'data']);

        $resolver->setDefaults([
            'cell_type' => null,
            'class_name' => null,
            'content_padding' => null,
            'default_content' => null,
            'name' => null,
            'orderable' => true,
            'order_data' => null,
            'order_sequence' => null,
            'searchable' => true,
            'title' => null,
            'visible' => true,
            'width' => null,
            'add_if' => null,
            'join_type' => 'leftJoin',
            'type_of_field' => null,
            'responsive_priority' => null,
            'sent_in_response' => true,
            'search_column_group' => null,
        ]);

        $resolver->setAllowedTypes('cell_type', ['null', 'string']);
        $resolver->setAllowedTypes('class_name', ['null', 'string']);
        $resolver->setAllowedTypes('content_padding', ['null', 'string']);
        $resolver->setAllowedTypes('dql', ['null', 'string']);
        $resolver->setAllowedTypes('data', ['null', 'string']);
        $resolver->setAllowedTypes('default_content', ['null', 'string']);
        $resolver->setAllowedTypes('name', ['null', 'string']);
        $resolver->setAllowedTypes('orderable', 'bool');
        $resolver->setAllowedTypes('order_data', ['null', 'array', 'int']);
        $resolver->setAllowedTypes('order_sequence', ['null', 'array']);
        $resolver->setAllowedTypes('searchable', 'bool');
        $resolver->setAllowedTypes('title', ['null', 'string']);
        $resolver->setAllowedTypes('visible', 'bool');
        $resolver->setAllowedTypes('width', ['null', 'string']);
        $resolver->setAllowedTypes('add_if', ['null', 'Closure']);
        $resolver->setAllowedTypes('join_type', 'string');
        $resolver->setAllowedTypes('type_of_field', ['null', 'string']);
        $resolver->setAllowedTypes('responsive_priority', ['null', 'int']);
        $resolver->setAllowedTypes('sent_in_response', ['bool']);
        $resolver->setAllowedTypes('search_column_group', ['null', 'string']);

        $resolver->setAllowedValues('cell_type', [null, 'th', 'td']);
        $resolver->setAllowedValues('join_type', [null, 'join', 'leftJoin', 'innerJoin']);
        $resolver->setAllowedValues('type_of_field', [...[null], ...array_keys(DoctrineType::getTypesMap())]);

        return $this;
    }

    // -------------------------------------------------
    // ColumnInterface
    // -------------------------------------------------

    public function dqlConstraint($dql): bool
    {
        if ($this->isCustomDql()) {
            return true;
        }

        return (bool) preg_match('/^[a-zA-Z0-9_\\-\\.]+$/', $dql);
    }

    public function isUnique(): bool
    {
        return false;
    }

    public function isAssociation(): bool
    {
        return str_contains($this->dql, '.');
    }

    public function isToManyAssociation(): bool
    {
        if (! $this->isAssociation()) {
            return false;
        }
        if (null === $this->typeOfAssociation) {
            return false;
        }

        return \in_array(ClassMetadataInfo::ONE_TO_MANY, $this->typeOfAssociation, true) || \in_array(ClassMetadataInfo::MANY_TO_MANY, $this->typeOfAssociation, true);
    }

    public function isSelectColumn(): bool
    {
        return true;
    }

    public function getOptionsTemplate(): string
    {
        return '@SgDatatables/column/column.html.twig';
    }

    public function addDataToOutputArray(array &$row): static
    {
        return $this;
    }

    public function renderCellContent(array &$row): static
    {
        return $this->isToManyAssociation() ? $this->renderToMany($row) : $this->renderSingleField($row);
    }

    public function renderPostCreateDatatableJsContent(): ?string
    {
        return null;
    }

    public function allowedPositions(): ?array
    {
        return null;
    }

    public function getColumnType(): string
    {
        return self::DATA_COLUMN;
    }

    public function isEditableContentRequired(array $row): bool
    {
        return false;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    public function getCellType(): ?string
    {
        return $this->cellType;
    }

    public function setCellType(?string $cellType): static
    {
        $this->cellType = $cellType;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function setClassName(?string $className): static
    {
        $this->className = $className;

        return $this;
    }

    public function getContentPadding(): ?string
    {
        return $this->contentPadding;
    }

    public function setContentPadding(?string $contentPadding): static
    {
        $this->contentPadding = $contentPadding;

        return $this;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getDefaultContent(): mixed
    {
        return $this->defaultContent;
    }

    public function setDefaultContent(mixed $defaultContent): static
    {
        $this->defaultContent = $defaultContent;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOrderable(): bool
    {
        return $this->orderable;
    }

    public function setOrderable(bool $orderable): static
    {
        $this->orderable = $orderable;

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function getOrderData(): int|array|null
    {
        if (\is_array($this->orderData)) {
            return $this->optionToJson($this->orderData);
        }

        return $this->orderData;
    }

    public function setOrderData(int|array|null $orderData): static
    {
        $this->orderData = $orderData;

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function getOrderSequence(): ?array
    {
        if (\is_array($this->orderSequence)) {
            return $this->optionToJson($this->orderSequence);
        }

        return $this->orderSequence;
    }

    public function setOrderSequence(?array $orderSequence): static
    {
        $this->orderSequence = $orderSequence;

        return $this;
    }

    public function getSearchable(): bool
    {
        return $this->searchable;
    }

    public function setSearchable(bool $searchable): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function setWidth(?string $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get join type.
     */
    public function getJoinType(): string
    {
        return $this->joinType;
    }

    /**
     * Set join type.
     */
    public function setJoinType(string $joinType): static
    {
        $this->joinType = $joinType;

        return $this;
    }

    /**
     * Get type of field.
     */
    public function getTypeOfField(): ?string
    {
        return $this->typeOfField;
    }

    /**
     * Set type of field.
     */
    public function setTypeOfField(?string $typeOfField): static
    {
        $this->typeOfField = $typeOfField;

        return $this;
    }

    public function getResponsivePriority(): ?int
    {
        return $this->responsivePriority;
    }

    public function setResponsivePriority(?int $responsivePriority): static
    {
        $this->responsivePriority = $responsivePriority;

        return $this;
    }

    public function getDql(): ?string
    {
        return $this->dql;
    }

    /**
     * @throws RuntimeException
     */
    public function setDql(?string $dql): static
    {
        if ($this->dqlConstraint($dql)) {
            $this->dql = $dql;
        } else {
            throw new RuntimeException("AbstractColumn::setDql(): {$dql} is not valid for this Column.");
        }

        return $this;
    }

    public function isCustomDql(): bool
    {
        return $this->customDql;
    }

    public function setCustomDql(bool $customDql): static
    {
        $this->customDql = $customDql;

        return $this;
    }

    public function getTwig(): ?Twig_Environment
    {
        return $this->twig;
    }

    public function setTwig(Twig_Environment $twig): static
    {
        $this->twig = $twig;

        return $this;
    }

    public function getRouter(): ?RouterInterface
    {
        return $this->router;
    }

    public function setRouter(RouterInterface $router): static
    {
        $this->router = $router;

        return $this;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function getDatatableName(): string
    {
        return $this->datatableName;
    }

    public function setDatatableName(string $datatableName): static
    {
        $this->datatableName = $datatableName;

        return $this;
    }

    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    public function setEntityClassName(string $entityClassName): static
    {
        $this->entityClassName = $entityClassName;

        return $this;
    }

    public function getTypeOfAssociation(): ?array
    {
        return $this->typeOfAssociation;
    }

    public function setTypeOfAssociation(?array $typeOfAssociation): static
    {
        $this->typeOfAssociation = $typeOfAssociation;

        return $this;
    }

    /**
     * Add a typeOfAssociation.
     */
    public function addTypeOfAssociation(int $typeOfAssociation): static
    {
        $this->typeOfAssociation[] = $typeOfAssociation;

        return $this;
    }

    public function getOriginalTypeOfField(): ?string
    {
        return $this->originalTypeOfField;
    }

    public function setOriginalTypeOfField(?string $originalTypeOfField): static
    {
        $this->originalTypeOfField = $originalTypeOfField;

        return $this;
    }

    public function getSentInResponse(): bool
    {
        return $this->sentInResponse;
    }

    public function setSentInResponse(bool $sentInResponse): static
    {
        $this->sentInResponse = $sentInResponse;

        return $this;
    }

    /**
     * Get the group name of columns to look at in a column search.
     */
    public function getSearchColumnGroup(): ?string
    {
        return $this->searchColumnGroup;
    }

    /**
     * Set the group name of columns to look at in a column search.
     */
    public function setSearchColumnGroup(?string $searchColumnGroup): static
    {
        $this->searchColumnGroup = $searchColumnGroup;

        return $this;
    }
}
