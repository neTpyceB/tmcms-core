// Generated by CoffeeScript 1.8.0
var IDgen, empty;

empty = function(v) {
  if (!isset(v) || v === '0' || v === 0 || v === null || v === '') {
    return true;
  } else {
    return false;
  }
};

IDgen = function() {
  return time().toString() + random_number(10000, 99999).toString();
};

//# sourceMappingURL=scripts.js.map