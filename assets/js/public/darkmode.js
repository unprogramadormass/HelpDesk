
        // 5. DARK MODE
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const themeIcon = document.getElementById('theme-icon');
        
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        }

        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            themeIcon.classList.toggle('rotate-180');
            
            const isDark = html.classList.contains('dark');
            localStorage.theme = isDark ? 'dark' : 'light';
            
            setTimeout(() => {
                themeIcon.classList.toggle('fa-sun');
                themeIcon.classList.toggle('fa-moon');
            }, 150);
            
            updateChartColors(isDark ? 'dark' : 'light');
        });
