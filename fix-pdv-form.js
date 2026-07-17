
const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'assets', 'controllers', 'pdv-form-controller.js');
let content = fs.readFileSync(filePath, 'utf8');

// Replace the Turbo.connectStreamSource block with Turbo.renderStreamMessage
content = content.replace(
  /\.then\(html => {\s+Turbo\.connectStreamSource[\s\S]+?}\)\(html\)\);\s+}\)/,
  '.then(html => {\n        Turbo.renderStreamMessage(html);\n      })'
);

fs.writeFileSync(filePath, content, 'utf8');
console.log('Fixed pdv-form-controller.js');
