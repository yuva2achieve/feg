<input type="hidden" name="oper" value="=">
{$import_source = DAO_ImportSource::getAll();}
<b>{$translate->_('search.value')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	{foreach from=$import_source item=import key=import_id}
		<label><input type="radio" value="{$import->id}" checked>{$import->name|capitalize}</label>
	{/foreach}
	<br>
</blockquote>

