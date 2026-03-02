from html.parser import HTMLParser

class BalanceParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.div_count = 0
        self.error_at = -1
    
    def handle_starttag(self, tag, attrs):
        if tag == "div":
            self.div_count += 1

    def handle_endtag(self, tag):
        if tag == "div":
            self.div_count -= 1
            if self.div_count == 0 and self.error_at == -1:
                self.error_at = self.getpos()[0]

parser = BalanceParser()
with open('hotspot-templates.php') as f:
    text = f.read()
    script_split = text.split('<script>')[0]
    parser.feed(script_split)
    
print("Final count before script:", parser.div_count, "Reached 0 at line:", parser.error_at)
