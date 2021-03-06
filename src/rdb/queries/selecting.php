<?php namespace r;

class Get extends ValuedQuery
{
    public function __construct(Table $table, $key, $index = null) {
        if (isset($index)) {
            if (!(is_object($index) && is_subclass_of($index, "\\r\\Query")))
                $index = new StringDatum($index);
        }
        if (!(is_object($key) && is_subclass_of($key, "\\r\\Query"))) {
            if (is_numeric($key))
                $key = new NumberDatum($key);
            else
                $key = new StringDatum($key);
        }
        $this->table = $table;
        $this->key = $key;
        $this->index = $index;
    }

    public function getPBTerm() {
        $term = new pb\Term();
        $term->set_type(pb\Term_TermType::PB_GET);
        $term->set_args(0, $this->table->getPBTerm());
        $term->set_args(1, $this->key->getPBTerm());
        if (isset($this->index)) {
            $term->set_args(2, $this->index->getPBTerm());
        }
        return $term;
    }
    
    private $table;
    private $key;
    private $index;
}

class Between extends ValuedQuery
{
    public function __construct(ValuedQuery $selection, $leftBound, $rightBound) {
        if (isset($leftBound) && !(is_object($leftBound) && is_subclass_of($leftBound, "\\r\\Query"))) $leftBound = nativeToDatum($leftBound);
        if (isset($rightBound) && !(is_object($rightBound) && is_subclass_of($rightBound, "\\r\\Query"))) $rightBound = nativeToDatum($rightBound);
        $this->selection = $selection;
        $this->leftBound = $leftBound;
        $this->rightBound = $rightBound;
    }
    
    public function getPBTerm() {
        $term = new pb\Term();
        $term->set_type(pb\Term_TermType::PB_BETWEEN);
        $term->set_args(0, $this->selection->getPBTerm());
        $i = 0;
        if (isset($this->leftBound)) {
            $pair = new pb\Term_AssocPair();
            $pair->set_key("left_bound");
            $pair->set_val($this->leftBound->getPBTerm());
            $term->set_optargs($i, $pair);
            ++$i;
        }
        if (isset($this->rightBound)) {
            $pair = new pb\Term_AssocPair();
            $pair->set_key("right_bound");
            $pair->set_val($this->rightBound->getPBTerm());
            $term->set_optargs($i, $pair);
            ++$i;
        };
        return $term;
    }
    
    private $selection;
    private $leftBound;
    private $rightBound;
}

class Filter extends ValuedQuery
{
    public function __construct(ValuedQuery $sequence, $predicate) {
        if (!(is_object($predicate) && is_subclass_of($predicate, "\\r\\Query"))) {
            try {
                $predicate = nativeToDatum($predicate);
                if (!is_subclass_of($predicate, "\\r\\Datum")) {
                    // $predicate is not a simple datum. Wrap it into a function:                
                    $predicate = new RFunction(array(new RVar('_')), $predicate);
                }
            } catch (RqlDriverError $e) {
                $predicate = nativeToFunction($predicate);
            }
        } else if (!(is_object($predicate) && is_subclass_of($predicate, "\\r\\FunctionQuery"))) {
            $predicate = new RFunction(array(new RVar('_')), $predicate);
        }
        $this->sequence = $sequence;
        $this->predicate = $predicate;
    }

    public function getPBTerm() {
        $term = new pb\Term();
        $term->set_type(pb\Term_TermType::PB_FILTER);
        $term->set_args(0, $this->sequence->getPBTerm());
        $term->set_args(1, $this->predicate->getPBTerm());
        return $term;
    }
    
    private $sequence;
    private $predicate;
}

?>
