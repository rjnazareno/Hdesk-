const fs = require('fs');
const path = require('path');
const postcss = require('postcss');
const tailwindcss = require('tailwindcss');
const autoprefixer = require('autoprefixer');

const inputPath = path.join(__dirname, 'assets/css/input.css');
const outputPath = path.join(__dirname, 'assets/css/tailwind.min.css');
const configPath = path.join(__dirname, 'tailwind.config.js');

async function build() {
  try {
    console.log('🔨 Building Tailwind CSS...');
    
    const input = fs.readFileSync(inputPath, 'utf8');
    const config = require(configPath);
    
    const result = await postcss([
      tailwindcss(config),
      autoprefixer(),
    ]).process(input, {
      from: inputPath,
      to: outputPath,
    });
    
    fs.writeFileSync(outputPath, result.css);
    
    const sizeKB = (fs.statSync(outputPath).size / 1024).toFixed(2);
    console.log(`✅ Build complete!`);
    console.log(`   Output: ${outputPath}`);
    console.log(`   Size: ${sizeKB} KB`);
  } catch (error) {
    console.error('❌ Build failed:', error.message);
    process.exit(1);
  }
}

build();
