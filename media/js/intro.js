
document.addEventListener('DOMContentLoaded', function()
{
	document.querySelectorAll('div.field-input span.delete').forEach(button => {
		button.addEventListener('click', (event) => {

			if(event.target.classList.contains('image'))
			{
				event.target.parentNode.parentNode.nextElementSibling.classList.remove('hidden');
				event.target.parentNode.parentNode.remove();
			}
			else if(event.target.classList.contains('file'))
			{
				event.target.parentNode.nextElementSibling.classList.remove('hidden');
				event.target.parentNode.remove();
			}
			else if(event.target.classList.contains('multiple-file'))
				event.target.parentNode.remove();
			else if(event.target.classList.contains('multiple-image'))
				event.target.parentNode.remove();
		})
	});
});