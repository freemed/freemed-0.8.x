<?
# function weekday
# $string_column � a coluna selecionada 
# $array_row � a linha atual do relat�rio

function weekday($string_column, $array_row)
{
	return date('W', $string_column);
}
?>