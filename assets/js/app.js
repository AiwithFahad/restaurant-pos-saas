function restoApplyTheme(dark){document.body.classList.toggle('dark',dark);document.documentElement.dataset.theme=dark?'dark':'light';localStorage.setItem('resto-theme',dark?'dark':'light');const t=document.getElementById('themeToggle');if(t)t.innerHTML=dark?'<i class="bi bi-sun"></i>':'<i class="bi bi-moon"></i>';}
restoApplyTheme(localStorage.getItem('resto-theme')==='dark');
document.getElementById('themeToggle')?.addEventListener('click',()=>restoApplyTheme(!document.body.classList.contains('dark')));
document.getElementById('sidebarToggle')?.addEventListener('click',()=>{document.body.classList.toggle('sb-collapsed');localStorage.setItem('resto-sb',document.body.classList.contains('sb-collapsed')?'1':'');});
if(localStorage.getItem('resto-sb')==='1')document.body.classList.add('sb-collapsed');
document.querySelectorAll('[data-confirm]')?.forEach(el=>el.addEventListener('click',ev=>{if(!confirm(el.dataset.confirm))ev.preventDefault();}));
