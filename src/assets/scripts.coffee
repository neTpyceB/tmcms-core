# Check if value is set and have value
empty = (v) ->
   if !isset(v) || v == '0' || v == 0 || v == null || v == '' then true else false

IDgen = ->
  time().toString() + random_number(10000, 99999).toString()