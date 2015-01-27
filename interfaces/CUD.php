<?php

interface CUD {
    public function insert($name);
    public function update(array $values);
    public function delete($ID);
}

?>
