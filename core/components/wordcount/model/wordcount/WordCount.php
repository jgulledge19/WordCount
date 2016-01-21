<?php

class WordCount {

    protected $modx;
    /**
     * @var xPDOQuery ~ see: https://rtfm.modx.com/xpdo/2.x/class-reference/xpdo/xpdo.newquery
     */
    protected $resourceQuery = null;
    /**
     * @var array
     */
    protected $countable_resource_columns = array();
    /**
     * @var array
     */
    protected $template_columns = array();
    /**
     * @var bool
     */
    protected $debug = false;
    /**
     * @var array
     */
    protected $config = array();
    /**
     * WordCount constructor.
     */
    public function __construct()
    {

    }

    /**
     * @param Modx $modx
     * @param null $countable_resource_columns
     */
    public function init(Modx &$modx, $countable_resource_columns=null)
    {
        $this->modx = &$modx;
        $this->resourceQuery = $this->modx->newQuery('modResource');
        if ( is_null($countable_resource_columns) ) {
            $this->countable_resource_columns = array(
                'pagetitle',
                'longtitle',
                'introtext',
                'description',
                'alias',
                'content',
                'menutitle'
            );
        } else {
            $this->countable_resource_columns = $countable_resource_columns;
        }

        $this->config = array(
            'stripTags' => true
        );
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function setConfig($name, $value)
    {
        $this->config[$name] = $value;
        return $this;
    }

    /**
     * @param string $column
     * @param mixed $filter ~ see xPDOQuery::where: https://rtfm.modx.com/xpdo/2.x/class-reference/xpdoquery/xpdoquery.where
     * @param $conjunction ~ AND or OR, default is AND
     *
     * @return $this ~ chainable
     */
    public function addResourceFilter($column, $filter, $conjunction='AND')
    {
        if ( $this->debug ) {
            echo PHP_EOL.'Set Filter: '.$conjunction;
        }
        $conditions = array(
            $column => $filter
        );
        if ( $conjunction == 'OR') {
            $this->resourceQuery->orCondition($conditions);
        } else {
            $this->resourceQuery->andCondition($conditions);
        }
        return $this;
    }
    /**
     * @param mixed $conditions ~ see xPDOQuery::where: https://rtfm.modx.com/xpdo/2.x/class-reference/xpdoquery/xpdoquery.where
     * @param $conjunction ~ AND or OR, default is AND
     *
     * @return $this ~ chainable
     */
    public function addResourceWhere($conditions, $conjunction=xPDOQuery::SQL_OR)
    {
        if ( $this->debug ) {
            echo PHP_EOL.'Set Where: ';print_r($conditions);
        }
        $this->resourceQuery->where(
            $conditions,
            $conjunction
        );
        return $this;
    }

    /**
     * @param bool $bool
     *
     * @return $this
     */
    public function setDebug($bool=true)
    {
        $this->debug = $bool;
        return $this;
    }

    /**
     * @return array|int
     */
    public function countResources()
    {
        if ( $this->debug ) {
            $this->resourceQuery->prepare();
            echo PHP_EOL.
                'SQL: '.PHP_EOL.
                $this->resourceQuery->toSQL().
                PHP_EOL.
                PHP_EOL;
        }
        // table:
        // page title, ID, URL, count
        // Totals
        $resources = $this->modx->getCollection('modResource', $this->resourceQuery);
        $total = 0;
        foreach ( $resources as $resource ) {
            $total += $this->countResource($resource->get('id'), $resource->toArray());
        }
        return $total;
    }

    /**
     * @param int $id
     * @param null|array $resource ~
     *
     * @return int
     */
    public function countResource($id, $resource=null)
    {
        $count = 0;
        if ( is_null($resource) ) {
            // @TODO query the resource
        }
        // first count the countable resource columns:
        foreach ( $this->countable_resource_columns as $column ) {
            if ( isset($resource[$column]) ) {
                $count += $this->countWords($resource[$column]);
            }
        }

        return $count;
    }

    /**
     * @param int $id
     * @param int $template_id
     *
     * @return int $count
     */
    public function countResourceTVs($id, $template_id)
    {
        $count = 0;
        $tvs = array();
        // now count used Template Variables (TVs)
        if ( !isset($this->template_columns[$template_id]) ) {
            // get all TV names:
            $tmplVars = $this->modx->getCollection('modTemplateVarTemplate', array('templateid' => $template_id));
            foreach( $tmplVars as $tv ) {
                $tvs[] = $tv->get('tmplvarid');
            }
            $this->template_columns[$template_id] = $tvs;
        } else {
            $tvs = $this->template_columns[$template_id];
        }

        $tvValues = $this->modx->getCollection(
            'modTemplateVarResource',
            array(
                'tmplvarid:IN' => $tvs,
                'contentid' => $id
            )
        );

        foreach ( $tvValues as $tvValue) {
            $count += $this->countWords($tvValue->get('value'));
        }

        /** One at a time
        foreach ( $tvs as $tv => $tv_id) {
        // More info: https://rtfm.modx.com/revolution/2.x/making-sites-with-modx/customizing-content/template-variables/accessing-template-variable-values-via-the-api
        $tvValue = $this->modx->getObject('modTemplateVarResource', array(
        'tmplvarid' => $tv_id,
        'contentid' => $id
        ));
        if ($tvValue) {
        $count += $this->countWords($tvValue->get('value'));
        }
        }
         */
        return $count;
    }

    /**
     * @return int
     */
    public function resourcePageTotal()
    {
        return $this->modx->getCount('modResource', $this->resourceQuery);
    }
    /**
     * @return int
     */
    public function countChunks()
    {
        $count = 0;
        // @TODO query filters
        $chunks = $this->modx->getCollection('modChunk');

        foreach ( $chunks as $chunk ) {
            $count += $this->countWords($chunk->get('snippet'));
        }
        return $count;
    }

    /**
     * @return int
     */
    public function countLexicons()
    {
        //
        $count = 0;
        // @TODO query filters
        $lexicons = $this->modx->getCollection('modLexiconEntry');

        foreach ( $lexicons as $lexicon ) {
            $count += $this->countWords($lexicon->get('value'));
        }
        return $count;
    }

    /**
     * @param string $string
     * @param int $format ~ see: http://php.net/manual/en/function.str-word-count.php
     *
     * @return int|array
     */
    public function countWords($string, $format=0)
    {
        if ( $this->config['stripTags'] ) {
            return str_word_count(strip_tags(strtolower($string)), $format);
        } else {
            return str_word_count(strtolower($string), $format);
        }
    }

    /**
     * @param string $string
     * @param int $format ~ default is 1, see: http://php.net/manual/en/function.str-word-count.php
     *
     * @return array
     */
    public function countGroupByWords($string, $format=1)
    {
        return array_count_values(str_word_count(strip_tags(strtolower($string)), $format));
    }
}