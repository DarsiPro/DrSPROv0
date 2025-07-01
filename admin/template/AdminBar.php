<!-- Основная панель администратора -->
<div class="AdminBar">
  <!-- Левая часть - основные ссылки -->
  <div class="links">
    {% if langs %}
    <!-- Выпадающий список языков -->
    <div class="dropdown" data-dropdown>
      <a>Язык</a>
      <div class="dropdown-menu">
        {% for i,l in langs %}
        <a href="{{ www_root }}/{{ l }}"{% if lang==l %} class="sel"{% endif %}>{{ l }}</a>
        {% endfor %}
      </div>
    </div>
    {% endif %}
    
    <!-- Статические ссылки -->
    <a href="//darsi.pro">Оф.сайт</a>
    <a href="//darsi.pro/forum/">Поддержка</a>
  </div>
  
  <!-- Правая часть - пользовательские элементы -->
  <div class="user">
    {% if user.id %}
      <!-- Для авторизованных пользователей -->
      <a href="{{ www_root }}/users/pm/">Сообщения</a>
      
      <!-- Выпадающее меню профиля -->
      <div class="dropdown" data-dropdown>
        <a href="{{ user.profile }}">{{ user.name }}</a>
        <div class="dropdown-menu">
          <img src="{{ user.avatar_url }}" alt="Аватар">
          <div>
            <a href="{{ user.profile }}">Профиль</a>
            <a href="{{ www_root }}/users/send_pm_form/">Сообщение</a>
            <a href="{{ www_root }}/users/edit_form/">Настройки</a>
            {% if drs_admin_access %}
            <a href="{{ www_root }}/admin/">Админка</a>
            {% endif %}
          </div>
        </div>
      </div>
      
      <a href="{{ www_root }}/users/logout">Выход</a>
    {% else %}
      <!-- Для гостей -->
      {{ ulogin }} <!-- Блок авторизации через соцсети -->
      
      <a href="{{ www_root }}/users/add_form">Регистрация</a>
      
      <!-- Форма входа -->
      <div class="dropdown" data-dropdown>
        <a>Войти</a>
        <div class="dropdown-menu">
          <form action="{{ www_root }}/users/login/" method="post">
            <input type="text" name="username" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <label>
              <input type="checkbox" name="autologin"> Автовход
            </label>
            <button type="submit">Войти</button>
          </form>
        </div>
      </div>
    {% endif %}
    
    <a href="{{ www_root }}/users/">Пользователи</a>
  </div>
</div>

<script>
// Ожидаем полной загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
  let currentOpenDropdown = null; // Текущее открытое меню
  
  /**
   * Закрывает все выпадающие меню
   */
  function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
      menu.classList.remove('open');
    });
    currentOpenDropdown = null;
  }
  
  /**
   * Обработчик клика по документу
   * Закрывает меню при клике вне их области
   */
  document.addEventListener('click', function(e) {
    if (!e.target.closest('[data-dropdown]')) {
      closeAllDropdowns();
    }
  });
  
  // Назначаем обработчики для всех выпадающих меню
  document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
    const toggle = dropdown.querySelector('a:first-child'); // Кнопка переключения
    const menu = dropdown.querySelector('.dropdown-menu');  // Само меню
    
    /**
     * Обработчик клика по кнопке меню
     */
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Если кликнули по уже открытому меню - закрываем
      if (currentOpenDropdown === menu) {
        closeAllDropdowns();
        return;
      }
      
      // Закрываем все и открываем текущее
      closeAllDropdowns();
      menu.classList.add('open');
      currentOpenDropdown = menu;
    });
    
    /**
     * Обработчик наведения на пункт меню
     * Автоматически переключает между открытыми меню
     */
    dropdown.addEventListener('mouseenter', function() {
      if (currentOpenDropdown) {
        closeAllDropdowns();
        menu.classList.add('open');
        currentOpenDropdown = menu;
      }
    });
  });
});
</script>