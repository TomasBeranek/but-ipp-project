#!/usr/bin/env python

 # ******************************* interpret.py *****************************
 #  Course: Principles of Programming languages (IPP) - FIT BUT
 #  Project name: IPPcode20 code interpreter in Python
 #  Author: Beranek Tomas (xberan46)
 #  Date: 7.4.2020
 # **************************************************************************

import sys
import xml.etree.ElementTree as ET
import re
from xml.sax import saxutils

def print_help():
    help = """Interpret XML reprezentace kodu

Program nacte XML reprezentaci programu a tento program s vyuzitim vstupu dle parametru přika-
zové řádky interpretuje a generuje výstup.

Parametry:
    --help          -vypise tuto napovedu
    --source=file   -soubor pro nacteni XML reprezentace
    --input=file    -soubor pro nacteni vstupu pro samotnou interpretaci

Alespon jeden z parametru (--source nebo --input) musi byt vzdy zadan. Pokud jeden z nich
chybi, tak jsou odpovidajici data nacitana ze standardniho vstupu."""

    print(help)

# function for getting order from instuction element
# function gets and converts order to int
# function is used for sorting instruction elements by order attribute
def get_order_attr(instr_elem):
    global order_dict
    try:
        order = int(instr_elem.attrib['order'])
    #order exists but not containing int number or order attribute doesnt exist
    except (ValueError, KeyError):
        sys.exit(32)
    if order > 0 and not (order in order_dict):
        order_dict[order] = True
        return order
    else:
        sys.exit(32) #order was negative/zero or already exists

def sort_instructions(program):
    program[:] = sorted(program, key=get_order_attr)

def check_program_atrributes(program):
    #required attribute
    if ('language' in program.attrib) and program.attrib['language']=='IPPcode20':
        #optional attributes 'name' and 'description'
        if ((len(program.attrib) == 1) or
            (len(program.attrib) == 2 and (('name' in program.attrib) or ('description' in program.attrib))) or
            (len(program.attrib) == 3 and ('name' in program.attrib) and ('description' in program.attrib))):
            return
        else:
            sys.exit(32)
    else:
        sys.exit(32)

def check_instruction_attributes(instruction):
    if ((len(instruction.attrib) == 2) and (instruction.tag == 'instruction') and
        ('order' in instruction.attrib) and ('opcode' in instruction.attrib)):
        return
    else:
        sys.exit(32)

def convert_ippcode_str(str):
    str = saxutils.unescape(str)
    ippcode_escape_seq = re.findall(r'\\\d\d\d', str)
    for seq in ippcode_escape_seq:
        str = str.replace(seq, chr(int(seq[1:4])))
    return str

def check_arg_attributes(arg, arg_number):
    if (arg.tag == ('arg'+str(arg_number)) and len(arg.attrib) == 1 and
        ('type' in arg.attrib) and len(arg) == 0 and
        arg.attrib['type'] in ['int', 'bool', 'string', 'nil', 'label', 'type', 'var']):
        pass
    else:
        sys.exit(32)

def is_var(arg, arg_number):
    check_arg_attributes(arg, arg_number)
    if arg.attrib['type'] == 'var':
        if re.match('^[LTG]F@([_\-$&%*!?]|[a-zA-Z])(\d|[a-zA-Z]|[_\-$&%*!?])*$', saxutils.unescape(arg.text)):
            return True
        else:
            sys.exit(32) #wrong lexem or wrong value for variable (syntax error)
    else:
        return False #not a variable

def is_label(arg, arg_number):
    check_arg_attributes(arg, arg_number)
    if arg.attrib['type'] == 'label':
        if re.match('^([_\-$&%*!?]|[a-zA-Z])(\d|[a-zA-Z]|[_\-$&%*!?])*$', saxutils.unescape(arg.text)):
            return True
        else:
            sys.exit(32)
    else:
        return False

def is_nil(arg, arg_number):
    check_arg_attributes(arg, arg_number)
    if arg.attrib['type'] == 'nil':
        if arg.text == 'nil':
            return True
        else:
            sys.exit(32)
    else:
        return False

def is_bool(arg, arg_number):
    check_arg_attributes(arg, arg_number)
    if arg.attrib['type'] == 'bool':
        if arg.text in ['true', 'false']:
            return True
        else:
            sys.exit(32)
    else:
        return False

def is_int(arg, arg_number):
    check_arg_attributes(arg, arg_number)
    if arg.attrib['type'] == 'int':
        try:
            int(arg.text)
            return True
        except ValueError:
            sys.exit(32)
    else:
        return False

def is_string(arg, arg_number):
    check_arg_attributes(arg, arg_number)
    if arg.attrib['type'] == 'string':
        if re.match('^([^\s\\\\#]|\\\\\d{3})*$', saxutils.unescape(arg.text)):
            return True
        else:
            sys.exit(32)
    else:
        return False

def is_type(arg, arg_number):
    check_arg_attributes(arg, arg_number)
    if arg.attrib['type'] == 'type':
        if arg.text in ['int', 'string', 'bool']:
            return True
        else:
            sys.exit(32)
    else:
        return False

def is_symb(arg, arg_number):
    if (is_var(arg, arg_number) or is_nil(arg, arg_number) or is_bool(arg, arg_number) or
        is_int(arg, arg_number) or is_string(arg, arg_number)):
        return True
    else:
        return False

#argument must be valid
def get_val(arg):
    global GF, TF, TF_created, LF_stack
    if arg.attrib['type'] == 'int':
        return ('int', int(arg.text))
    elif arg.attrib['type'] == 'bool':
        return ('bool', arg.text == 'true')
    elif arg.attrib['type'] == 'string':
        return ('string', convert_ippcode_str(arg.text))
    elif arg.attrib['type'] == 'nil':
        return ('nil', 'nil')
    elif arg.attrib['type'] == 'label':
        return ('label', saxutils.unescape(arg.text))
    elif arg.attrib['type'] == 'type':
        return ('type', arg.text)
    else: #var
        var = arg.text.split('@')
        if var[0] == 'GF':
            if var[1] in GF:
                if GF[var[1]][0] == None:
                    raise IndexError('56') #variable is not initialized
                else:
                    return GF[var[1]]
            else:
                sys.exit(54) #variable is not defined
        elif var[0] == 'TF':
            if not TF_created: #TF is not created
                sys.exit(55)
            elif var[1] in TF:
                if TF[var[1]][0] == None:
                    raise IndexError('56')
                else:
                    return TF[var[1]]
            else:
                sys.exit(54) #variable is not defined
        else: #LF
            if not LF_stack: #LF doesnt exist
                sys.exit(55)
            elif var[1] in LF_stack[-1]:
                if LF_stack[-1][var[1]][0] == None:
                    raise IndexError('56')
                else:
                    return LF_stack[-1][var[1]]
            else:
                sys.exit(54) #variable is not defined

#value must be tuple and var must contain xml arg var
def insert_value_to_var(var, value):
    global GF, TF, TF_created, LF_stack
    var = saxutils.unescape(var.text).split('@')
    if var[0] == 'GF':
        if var[1] in GF:
            GF[var[1]] = value
        else:
            sys.exit(54) #variable is not defined
    elif var[0] == 'TF':
        if not TF_created: #TF is not created
            sys.exit(55)
        elif var[1] in TF:
            TF[var[1]] = value
        else:
            sys.exit(54) #variable is not defined
    else: #LF
        if not LF_stack: #LF doesnt exist
            sys.exit(55)
        elif var[1] in LF_stack[-1]:
            LF_stack[-1][var[1]] = value
        else:
            sys.exit(54) #variable is not defined

#first arg must be XML instruction element and others strings containing one of:
#   -nil, var, int, string, label, bool, type, symb is_
def check_args(*args):
    instruction = args[0]
    args = args[1:]
    if len(instruction) == len(args):
        arg_number = 1
        for arg in args:
            if instruction[arg_number - 1].text == None:
                instruction[arg_number - 1].text = ""
            if (is_int(instruction[arg_number - 1], arg_number) or
                is_bool(instruction[arg_number - 1], arg_number) or
                is_string(instruction[arg_number - 1], arg_number) or
                is_nil(instruction[arg_number - 1], arg_number) or
                is_label(instruction[arg_number - 1], arg_number) or
                is_type(instruction[arg_number - 1], arg_number) or
                is_var(instruction[arg_number - 1], arg_number)):
                if globals()['is_' + arg](instruction[arg_number - 1], arg_number):
                    arg_number = arg_number + 1
                else:
                    sys.exit(53) #invalid type of argument
            else:
                sys.exit(32) #invalid argument
    else:
        sys.exit(32) #invalid number of arguments


############ INSTRUCTIONS ######################################################

def i_move(instruction):
    check_args(instruction, 'var', 'symb')
    insert_value_to_var(instruction[0], get_val(instruction[1]))

def i_createframe(instruction):
    global TF, TF_created
    check_args(instruction)
    TF.clear()
    TF_created = True

def i_pushframe(instruction):
    global LF_stack, TF, TF_created
    check_args(instruction)
    if TF_created:
        LF_stack.append(TF.copy())
        TF_created = False
    else:
        sys.exit(55)

def i_popframe(instruction):
    global LF_stack, TF, TF_created
    check_args(instruction)
    TF.clear()
    TF_created = True
    try:
        TF = LF_stack.pop()
    except IndexError:
        sys.exit(55)

def i_defvar(instruction):
    global GF, LF_stack, TF, TF_created
    check_args(instruction, 'var')
    var = instruction[0].text.split('@')
    if var[0] == 'GF':
        if var[1] in GF:
            sys.exit(52) #redefinition in GF
        else:
            GF[var[1]] = (None, None)
    elif var[0] == 'TF':
        if not TF_created: #TF is not created
            sys.exit(55)
        elif var[1] in TF: #redefinition in TF
            sys.exit(52)
        else:
            TF[var[1]] = (None, None)
    else: #LF
        if not LF_stack: #LF doesnt exist
            sys.exit(55)
        elif var[1] in LF_stack[-1]: #redefinition in top LF
            sys.exit(52)
        else:
            LF_stack[-1][var[1]] = (None, None)

def i_call(instruction):
    global instruction_cnt, instruction_cnt_stack
    check_args(instruction, 'label')
    instruction_cnt_stack.append(instruction_cnt + 1)
    label_name = get_val(instruction[0])[1]
    if label_name  in label:
        return label[label_name]
    else:
        sys.exit(52)

def i_return(instruction):
    global instruction_cnt_stack
    check_args(instruction)
    if instruction_cnt_stack:
        return instruction_cnt_stack.pop()
    else:
        sys.exit(56)  #empty stack

def i_pushs(instruction):
    global stack
    check_args(instruction, 'symb')
    stack.append(get_val(instruction[0]))

def i_pops(instruction):
    global stack
    check_args(instruction, 'var')
    if stack:
        insert_value_to_var(instruction[0], stack.pop())
    else:
        sys.exit(56)

def i_add(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == 'int' and symb2[0] == 'int':
        insert_value_to_var(instruction[0], ('int', symb1[1] + symb2[1]))
    else:
        sys.exit(53)

def i_sub(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == 'int' and symb2[0] == 'int':
        insert_value_to_var(instruction[0], ('int', symb1[1] - symb2[1]))
    else:
        sys.exit(53)

def i_mul(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == 'int' and symb2[0] == 'int':
        insert_value_to_var(instruction[0], ('int', symb1[1] * symb2[1]))
    else:
        sys.exit(53)

def i_idiv(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == 'int' and symb2[0] == 'int':
        if symb2[1] != 0:
            insert_value_to_var(instruction[0], ('int', symb1[1] // symb2[1]))
        else:
            sys.exit(57)
    else:
        sys.exit(53)

def i_lt(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == symb2[0] and symb1[0] in ['int', 'bool', 'string']:
        insert_value_to_var(instruction[0], ('bool', symb1[1] < symb2[1]))
    else:
        sys.exit(53)

def i_gt(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == symb2[0] and symb1[0] in ['int', 'bool', 'string']:
        insert_value_to_var(instruction[0], ('bool', symb1[1] > symb2[1]))
    else:
        sys.exit(53)

def i_eq(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == symb2[0] and symb1[0] in ['int', 'bool', 'string', 'nil']:
        insert_value_to_var(instruction[0], ('bool', symb1[1] == symb2[1]))
    else:
        if symb1[0] == 'nil' or symb2[0] == 'nil':
            insert_value_to_var(instruction[0], ('bool', False))
        else:
            sys.exit(53)

def i_and(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == 'bool' and symb2[0] == 'bool':
        insert_value_to_var(instruction[0], ('bool', symb1[1] and symb2[1]))
    else:
        sys.exit(53)

def i_or(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == 'bool' and symb2[0] == 'bool':
        insert_value_to_var(instruction[0], ('bool', symb1[1] or symb2[1]))
    else:
        sys.exit(53)

def i_not(instruction):
    check_args(instruction, 'var', 'symb')
    symb1 = get_val(instruction[1])
    if symb1[0] == 'bool':
        insert_value_to_var(instruction[0], ('bool', not symb1[1]))
    else:
        sys.exit(53)

def i_int2char(instruction):
    check_args(instruction, 'var', 'symb')
    symb1 = get_val(instruction[1])
    if symb1[0] == 'int':
        try:
            insert_value_to_var(instruction[0], ('string', chr(symb1[1])))
        except ValueError:
            sys.exit(58)
    else:
        sys.exit(53)

def i_stri2int(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == 'string' and symb2[0] == 'int':
        try:
            insert_value_to_var(instruction[0], ('int', ord(symb1[1][symb2[1]])))
        except IndexError:
            sys.exit(58)
    else:
        sys.exit(53)

def i_read(instruction):
    global input_passed, input_list
    check_args(instruction, 'var', 'type')
    type = get_val(instruction[1])[1]
    try:
        if input_passed:
            input_value = input_list.pop(0).replace("\n", "")
        else:
            input_value = str(input())

        if type == 'int':
            insert_value_to_var(instruction[0], ('int', int(input_value)))
        elif type == 'string':
            insert_value_to_var(instruction[0], ('string', input_value))
        else: #bool
            if input_value.upper() == 'TRUE':
                insert_value_to_var(instruction[0], ('bool', True))
            else:
                insert_value_to_var(instruction[0], ('bool', False))
    except (EOFError, ValueError):
        insert_value_to_var(instruction[0], ('nil', 'nil'))
    except IndexError:
        insert_value_to_var(instruction[0], ('nil', 'nil'))

def i_write(instruction):
    check_args(instruction, 'symb')
    symb1 = get_val(instruction[0])
    if symb1[0] == 'bool':
        if symb1[1]:
            print('true', end='')
        else:
            print('false', end='')
    elif symb1[0] == 'nil':
        print('', end='')
    else:
        print(symb1[1], end='')

def i_concat(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == 'string' and symb2[0] == 'string':
        insert_value_to_var(instruction[0], ('string', symb1[1] + symb2[1]))
    else:
        sys.exit(53)

def i_strlen(instruction):
    check_args(instruction, 'var', 'symb')
    symb1 = get_val(instruction[1])
    if symb1[0] == 'string':
        insert_value_to_var(instruction[0], ('int', len(symb1[1])))
    else:
        sys.exit(53)

def i_getchar(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if symb1[0] == 'string' and symb2[0] == 'int':
        try:
            insert_value_to_var(instruction[0], ('string', symb1[1][symb2[1]]))
        except IndexError:
            sys.exit(58)
    else:
        sys.exit(53)

def i_setchar(instruction):
    check_args(instruction, 'var', 'symb', 'symb')
    var   = get_val(instruction[0])
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if var[0] == 'string' and symb1[0] == 'int' and symb2[0] == 'string':
        try:
            var = var[1][:symb1[1]] + symb2[1][0] + var[1][symb1[1] + 1:]
            insert_value_to_var(instruction[0], ('string', var))
        except IndexError:
            sys.exit(58)
    else:
        sys.exit(53)

def i_type(instruction):
    check_args(instruction, 'var', 'symb')
    try:
        symb1 = get_val(instruction[1])
        insert_value_to_var(instruction[0], ('string', symb1[0]))
    except IndexError: #IndexError is thrown when variable is unitialized
        insert_value_to_var(instruction[0], ('string', ''))

def i_label(instruction):
    global instruction_cnt, label
    check_args(instruction, 'label')
    label_name = get_val(instruction[0])[1]
    if label_name in label and label[label_name] != instruction_cnt:
        sys.exit(52) #redefinition
    else:
        label[label_name] = instruction_cnt

def i_jump(instruction):
    global instruction_cnt, label
    check_args(instruction, 'label')
    label_name = get_val(instruction[0])[1]
    if label_name  in label:
        return label[label_name]
    else:
        sys.exit(52)

def i_jumpifeq(instruction):
    global instruction_cnt, label
    check_args(instruction, 'label', 'symb', 'symb')
    label_name = get_val(instruction[0])[1]
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if label_name in label:
        if symb1[0] == symb2[0] and symb1[0] in ['int', 'bool', 'string', 'nil']:
            if symb1[1] == symb2[1]:
                return label[label_name]
            else:
                pass #do not jump
        else:
            if symb1[0] == 'nil' or symb2[0] == 'nil':
                pass #do not jump
            else:
                sys.exit(53)
    else:
        sys.exit(52)

def i_jumpifneq(instruction):
    global instruction_cnt, label
    check_args(instruction, 'label', 'symb', 'symb')
    label_name = get_val(instruction[0])[1]
    symb1 = get_val(instruction[1])
    symb2 = get_val(instruction[2])
    if label_name in label:
        if symb1[0] == symb2[0] and symb1[0] in ['int', 'bool', 'string', 'nil']:
            if symb1[1] != symb2[1]:
                return label[label_name]
            else:
                pass #do not jump
        else:
            if symb1[0] == 'nil' or symb2[0] == 'nil':
                return label[label_name]
            else:
                sys.exit(53)
    else:
        sys.exit(52)

def i_exit(instruction):
    check_args(instruction, 'symb')
    symb1 = get_val(instruction[0])
    if symb1[0] == 'int':
        if 0 <= symb1[1] <= 49:
            sys.exit(symb1[1])
        else:
            sys.exit(57)
    else:
        sys.exit(53)

def i_dprint(instruction):
    check_args(instruction, 'symb')
    symb1 = get_val(instruction[0])
    print(symb1[1], file=sys.stderr)

def i_break(instruction):
    global instruction_cnt, TF_created, TF, LF_stack, label, stack
    check_args(instruction)
    print('Pozice instrukce (cislovano od 0): ' + str(instruction_cnt), file=sys.stderr)
    print('TF vytvoren: ' + str(TF_created), file=sys.stderr)
    if TF_created:
        print('TF obsah:', file=sys.stderr)
        print(TF, file=sys.stderr)
    print('GF obsah:', file=sys.stderr)
    print(GF, file=sys.stderr)
    print('LF obsah (vrchol zasobniku je dole): ', file=sys.stderr)
    for frame in LF_stack:
        print(frame, file=sys.stderr)
    print('Definovana navest a jejich radky (cislovano od 0):', file=sys.stderr)
    print(label, file=sys.stderr)
    print('Zasobnik (vrchol je vpravo):', file=sys.stderr)
    print(stack, file=sys.stderr)

def process_instruction(instruction):
    instruction_switch = {
        'MOVE':         i_move,
        'CREATEFRAME':  i_createframe,
        'PUSHFRAME':    i_pushframe,
        'POPFRAME':     i_popframe,
        'DEFVAR':       i_defvar,
        'CALL':         i_call,
        'RETURN':       i_return,
        'PUSHS':        i_pushs,
        'POPS':         i_pops,
        'ADD':          i_add,
        'SUB':          i_sub,
        'MUL':          i_mul,
        'IDIV':         i_idiv,
        'LT':           i_lt,
        'GT':           i_gt,
        'EQ':           i_eq,
        'AND':          i_and,
        'OR':           i_or,
        'NOT':          i_not,
        'INT2CHAR':     i_int2char,
        'STRI2INT':     i_stri2int,
        'READ':         i_read,
        'WRITE':        i_write,
        'CONCAT':       i_concat,
        'STRLEN':       i_strlen,
        'GETCHAR':      i_getchar,
        'SETCHAR':      i_setchar,
        'TYPE':         i_type,
        'LABEL':        i_label,
        'JUMP':         i_jump,
        'JUMPIFEQ':     i_jumpifeq,
        'JUMPIFNEQ':    i_jumpifneq,
        'EXIT':         i_exit,
        'DPRINT':       i_dprint,
        'BREAK':        i_break
    }
    try:
        fun = instruction_switch[instruction.attrib['opcode'].upper()]
        return fun(instruction) #if jump is required internal number of instruction to jump is returned
    except KeyError:
        sys.exit(32) #unknown opcode
    except IndexError:
        sys.exit(56)

#missing parametr or forbidden combination 10
#error when opening input file 11
#error when openin output file 12

######################### MAIN #################################################
source_file = sys.stdin
source_passed = False
input_file = sys.stdin
sys.argv.pop(0)

#global variables
input_passed = False
input_list = [] #list of inputs, when input is laoded from a file

#arg parsing
if 1 <= len(sys.argv) <= 3:
    for param in sys.argv:
        if param == "--help" and len(sys.argv) == 1:
            print_help()
            sys.exit(10)
        elif param[:9] == "--source=" and not source_passed:
            source_file = param[9:]
            source_passed = True
        elif param[:8] == "--input=" and not input_passed:
            input_file = param[8:]
            input_passed = True
        else:
            sys.exit(10)
else:
    sys.exit(10)

#load xml
try:
    if input_passed:
        with open(input_file) as in_f:
            input_list = in_f.readlines()
    tree = ET.parse(source_file)
except (IOError, OSError):
    sys.exit(11)
except ET.ParseError:
    sys.exit(31)

#load root element 'program'
program = tree.getroot()

order_dict = {} #dictionary to safe existing order numbers
sort_instructions(program)
check_program_atrributes(program)

#global variables
GF = {}                     #global frame
TF = {}                     #temporary frame
TF_created = False          #variable to determine if TF has been created
LF_stack = []               #stack of local frames
stack = []                  #program's stack, used by stack instructions
instruction_cnt = 0         #internal instruction counter
instruction_cnt_stack = []  #stack for saving instruction's internal order for return instructions
label = {}                  #dictionary of labels and lines

#load labels
for i in program:
    check_instruction_attributes(i)
    if i.attrib['opcode'].upper() == 'LABEL':
        i_label(i)
    instruction_cnt = instruction_cnt + 1

instruction_cnt = 0
#loop over every instruction element
while instruction_cnt < len(program):
    check_instruction_attributes(program[instruction_cnt])
    jump_to = process_instruction(program[instruction_cnt])

    #debug prints
    '''print('Command: ' + program[instruction_cnt].attrib['opcode'] + program[instruction_cnt].attrib['order'])
    print('GF', end='')
    print(GF)
    print('TF', end='')
    print(TF)
    print('LF', end='')
    print(LF_stack)
    print('STACK', end='')
    print(stack)
    print('Labels')
    print(label)'''

    instruction_cnt = instruction_cnt + 1
    if jump_to != None:
        instruction_cnt = jump_to

 # end of file interpret.py
