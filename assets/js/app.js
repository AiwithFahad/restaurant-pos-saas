document.getElementById('themeToggle')?.addEventListener('click',()=>{document.body.classList.toggle('dark');localStorage.setItem('resto-theme',document.body.classList.contains('dark')?'dark':'light');});
if(localStorage.getItem('resto-theme')==='dark')document.body.classList.add('dark');
document.querySelectorAll('[data-confirm]')?.forEach(el=>el.addEventListener('click',ev=>{if(!confirm(el.dataset.confirm))ev.preventDefault();}));
