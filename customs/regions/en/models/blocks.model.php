<?php
class Blocks extends Model
{
	protected $name = 'Text blocks';
	
	protected $model_elements = [
		['Active', 'bool', 'active', ['on_create' => true]],
		['Name', 'char', 'name', ['required' => true]],
		['Content', 'text', 'content', ['rich_text' => true]]
	];
}