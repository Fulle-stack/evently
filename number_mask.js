// Находим наше поле ввода по ID и сохраняем его в переменную phoneInput, чтобы дальше с ним работать.
const phoneInput = document.getElementById('phone'); 

const phoneMask = IMask(phoneInput, {
    mask: '+{7} (000) 000-00-00', //шаблон номера
    lazy: true, // показывать маску даже когда поле пустое
    placeholderChar: '_' //символ, которым заполняются пустые места (подчеркивание)
});