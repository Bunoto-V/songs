<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>היפוך טקסט</title>
	 <link rel="stylesheet" href="style.css?ver=<?= time(); ?>">
</head>
<body>
    <nav>
        <div class="nav-content">
            <div class="nav-title">
                <img src="vl-logo.png" alt="Logo">
                <h1>היפוך טקסט</h1>
            </div>
            <div class="nav-links">
                <a href="./">שירים</a>
                <a href="rev.php" class="active">היפוך טקסט</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="text-converter">
            <h2>היפוך טקסט, הופך טקסט</h2>
            <div class="subtitle">היפוך טקסט אונליין, תוכנה שהופכת טקסט בעברית חינם. הופך עברית לקריאה אחת</div>

            <textarea id="textInput" placeholder="הכניסו טקסט כאן..." dir="rtl"></textarea>

            <div class="button-group">
                <button onclick="reverseText()" class="btn-secondary">הפוך טקסט</button>
                <button onclick="copyText()">העתקה</button>
                <button onclick="flipToLeft()">הפוך לצד שמאל</button>
                <button onclick="flipToRight()">הפוך לצד ימין</button>
            </div>

            <div class="button-group">
                <button onclick="setDirection('ltr')">שמאל לימין (LTR)</button>
                <button onclick="setDirection('rtl')">ימין לשמאל (RTL)</button>
                <button class="align-button" onclick="setTextAlign('right')">⇚</button>
                <button class="align-button" onclick="setTextAlign('center')">≡</button>
                <button class="align-button" onclick="setTextAlign('left')">⇛</button>
            </div>

            <div class="button-group">
                <button onclick="reverseLines()">הפוך בתוך שורות</button>
                <button onclick="reverseWithoutEnglish()">היפוך בלי אנגלית</button>
                <button onclick="reverseWithoutNumbers()">היפוך בלי מספרים</button>
                <button onclick="reverseWords()">היפוך סדר המילים</button>
            </div>
        </div>
    </div>

    <script>
        const textarea = document.getElementById('textInput');

        function copyText() {
            textarea.select();
            document.execCommand('copy');
        }

        function setDirection(dir) {
            textarea.dir = dir;
        }

        function setTextAlign(align) {
            textarea.style.textAlign = align;
        }

        function reverseText() {
            textarea.value = textarea.value.split('').reverse().join('');
        }

        function flipToLeft() {
            textarea.dir = 'ltr';
            textarea.style.textAlign = 'left';
        }

        function flipToRight() {
            textarea.dir = 'rtl';
            textarea.style.textAlign = 'right';
        }

        function reverseLines() {
            const lines = textarea.value.split('\n');
            textarea.value = lines.map(line => line.split('').reverse().join('')).join('\n');
        }

        function reverseWithoutEnglish() {
            const text = textarea.value;
            const englishPattern = /[a-zA-Z]+/g;
            let reversed = text.split('').reverse().join('');
            
            const matches = Array.from(text.matchAll(englishPattern));
            for (const match of matches) {
                const word = match[0];
                const pos = text.length - 1 - match.index - word.length;
                reversed = reversed.substring(0, pos) + word + reversed.substring(pos + word.length);
            }
            
            textarea.value = reversed;
        }

        function reverseWithoutNumbers() {
            const text = textarea.value;
            const numberPattern = /\d+/g;
            let reversed = text.split('').reverse().join('');
            
            const matches = Array.from(text.matchAll(numberPattern));
            for (const match of matches) {
                const num = match[0];
                const pos = text.length - 1 - match.index - num.length;
                reversed = reversed.substring(0, pos) + num + reversed.substring(pos + num.length);
            }
            
            textarea.value = reversed;
        }

        function reverseWords() {
            textarea.value = textarea.value.split(' ').reverse().join(' ');
        }
    </script>
</body>
</html>