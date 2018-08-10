import collections
import importlib.util
import sys

#
# _import
# Impor package dengan input string
#
def _import(name):
    components = name.split('.')
    mod = __import__(components[0])
    for comp in components[1:]:
        mod = getattr(mod, comp)
    return mod

#
# _reverse
# Mirror dari data, cth: [1,2,3,4] => [4,3,2,1]
#
def _reverse(seq):
    SeqType = type(seq)
    emptySeq = SeqType()
    if seq == emptySeq:
        return emptySeq
    restrev = _reverse(seq[1:])
    first = seq[0:1]
    result = restrev + first
    return result

#
# LOAD MODULE
# Memuat module ke system
#
def load_module(name):
    if name in sys.modules:
        return sys.modules[name]
    arr_name = name.split('.')
    len_name = len(arr_name)
    spec = None
    for i in range(0, len_name):
        lidx = len_name - i
        try:
            spec = importlib.util.find_spec('.'.join(arr_name[0:lidx]))
            if spec is not None:
                break
        except Exception:
            pass
    if spec is None:
        return None
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    for i in range(lidx, len_name):
        if not hasattr(module, arr_name[i]):
            return None
        module = getattr(module, arr_name[i])
    return module
