<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>מהפך טקסט עברית</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: #262626;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(255, 140, 0, 0.3);
            max-width: 800px;
            width: 100%;
            border: 2px solid #ff8c00;
        }

        h1 {
            color: #ff8c00;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 0 0 20px rgba(255, 140, 0, 0.5);
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: #ff8c00;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 1.1em;
        }

        textarea {
            width: 100%;
            min-height: 200px;
            padding: 15px;
            border: 2px solid #ff8c00;
            border-radius: 10px;
            background: #1a1a1a;
            color: #ffffff;
            font-size: 16px;
            font-family: 'Arial', sans-serif;
            resize: vertical;
            transition: all 0.3s ease;
        }

        textarea:focus {
            outline: none;
            box-shadow: 0 0 20px rgba(255, 140, 0, 0.5);
            border-color: #ffa500;
        }

        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ff8c00;
            border-radius: 10px;
            background: #1a1a1a;
            color: #ffffff;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input[type="number"]:focus {
            outline: none;
            box-shadow: 0 0 20px rgba(255, 140, 0, 0.5);
            border-color: #ffa500;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        button {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .btn-convert {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6600 100%);
            color: #1a1a1a;
            box-shadow: 0 5px 15px rgba(255, 140, 0, 0.4);
        }

        .btn-convert:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 140, 0, 0.6);
        }

        .btn-copy {
            background: #1a1a1a;
            color: #ff8c00;
            border: 2px solid #ff8c00;
            box-shadow: 0 5px 15px rgba(255, 140, 0, 0.2);
        }

        .btn-copy:hover {
            background: #ff8c00;
            color: #1a1a1a;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 140, 0, 0.4);
        }

        .btn-copy:active {
            transform: translateY(0);
        }

        .copied-msg {
            text-align: center;
            color: #00ff00;
            margin-top: 15px;
            font-weight: bold;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .copied-msg.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 מהפך טקסט</h1>
        
        <div class="input-group">
            <label for="textInput">טקסט:</label>
            <textarea id="textInput" placeholder="הדבק כאן את הטקסט בעברית..."></textarea>
        </div>

        <div class="input-group">
            <label for="wordsPerLine">מספר מילים בשורה:</label>
            <input type="number" id="wordsPerLine" value="5" min="1" max="50">
        </div>

        <div class="button-group">
            <button class="btn-convert" onclick="convertText()">החלף</button>
            <button class="btn-copy" onclick="copyToClipboard()">העתק ללוח</button>
        </div>

        <div class="copied-msg" id="copiedMsg">✓ הועתק בהצלחה!</div>
    </div>

    <script>
        function reverseString(str) {
            return str.split('').reverse().join('');
        }

        function convertText() {
            const textarea = document.getElementById('textInput');
            const wordsPerLine = parseInt(document.getElementById('wordsPerLine').value) || 5;
            const text = textarea.value;
            
            if (!text.trim()) {
                alert('אנא הזן טקסט');
                return;
            }

            const originalLines = text.split('\n');
            const resultLines = [];
            
            for (let line of originalLines) {
                if (!line.trim()) {
                    resultLines.push('');
                    continue;
                }
                
                const words = line.trim().split(/\s+/);
                const lineResults = [];
                
                for (let i = 0; i < words.length; i += wordsPerLine) {
                    const chunk = words.slice(i, i + wordsPerLine);
                    const reversedWords = chunk.map(word => reverseString(word)).reverse();
                    lineResults.push(reversedWords.join(' '));
                }
                
                resultLines.push(lineResults.join('\n'));
            }
            
            textarea.value = resultLines.join('\n');
        }

        function copyToClipboard() {
            const textarea = document.getElementById('textInput');
            const text = textarea.value;
            
            if (!text) {
                alert('אין טקסט להעתקה');
                return;
            }

            textarea.select();
            document.execCommand('copy');
            
            const msg = document.getElementById('copiedMsg');
            msg.classList.add('show');
            setTimeout(() => {
                msg.classList.remove('show');
            }, 2000);
        }
    </script>
</body>
</html>