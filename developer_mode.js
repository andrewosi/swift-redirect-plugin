var fs = require('fs')

item = 'swift-redirect.php';
var data = fs.readFileSync(item, 'utf8')
var mapObj = {
  SWIFT_REDIRECT_PRODUCTION: 'SWIFT_REDIRECT_DEVELOPMENT',
}
var result = data.replace(/SWIFT_REDIRECT_PRODUCTION/gi, function (matched) {
  return mapObj[matched]
})
fs.writeFile(item, result, 'utf8', function (err) {
  if (err) return console.log(err)
})
console.log('✅  Development asset enqueued!')
