function gen(str,tabu,len,repeat,tags,tabuhide) { 
     var str, // Получаем текст из которого нужно выделить теги
          tabu = tabuhide + tabu.val(), // Получаем слова-исключения
          len = parseInt(len.val()), // Минимальная длинна тега
	      repeat = parseInt(repeat.val()), // Минимальное количество его повторений
	      tags, output = '';

     var  tabu = tabu.split(/\s*,\s*/), obj_tabu = {};
     for (var i=0; i<tabu.length; i++)  {obj_tabu[tabu[i]]=!0}; //сформировали обьект из запретных слов
     str = str.replace(/<[^>]*>/g, ' '); //выкусили теги
     str = str.replace(/[^a-zа-яё\s]/gi, ' '); //убрали небуквы
     str = str.split(/\s+/); //сформировали массив слов из строки
	 
     var obj_output = {};
     for (var i=0; i<str.length; i++)  {
       var word = str[i];
       if(word.length >= len && !obj_tabu[word])  obj_output[word]=(obj_output[word]||0)+1; // сформировали обьект из разрешённых слов и нужной длины
	 } 
	 
     var output = [];
     for(var word in obj_output) if (obj_output[word] >= repeat) output.push(word); //отобрали слова с нужным повторением
	 
	 output = output.join(); // создали строку из элементов массива через запятую
	 
     return tags.val(output);
}