function filterSelection(category) {
  const items = document.querySelectorAll('.filter-item');
  const buttons = document.querySelectorAll('.filter-btn');

  // เปลี่ยน active button
  buttons.forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');

  items.forEach(item => {
    if (category === 'all') {
      item.style.display = 'block';
    } else {
      if (item.dataset.category === category) {
        item.style.display = 'block';
      } else {
        item.style.display = 'none';
      }
    }
  });
}




